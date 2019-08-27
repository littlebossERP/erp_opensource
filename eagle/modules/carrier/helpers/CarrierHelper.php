<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\carrier\helpers;
use eagle\modules\carrier\models\SysCarrierParam;
use yii;
use yii\helpers\Url;
use yii\helpers\Html;
use eagle\models\SysShippingMethod;
use eagle\modules\carrier\models\SysShippingService;
use common\helpers\Helper_Array;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\carrier\models\SysCarrier;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\PriceministerOrderHelper;

/**
 * CarrierHelper.
 *
 */
class CarrierHelper {
	public static $carrier_step = array(
			0=>'待上传至物流商',
			1=>'待交运',
			2=>'待获取物流号',
			3=>'待打印物流单',
			4=>'已取消',
			5=>'重新发货',
			6=>'物流已完成',
	);
	public static $address_list = ['shippingfrom'=>'发货地址','pickupaddress'=>'揽收地址','returnaddress'=>'退货地址','shippingfrom_en'=>'发货地址(英文)'];
	
	//设定cdiscount支持的买家可选运输商
	public static $cdiscountShippingServices = array(
			'DHL'=>'DHL',
			'EMS'=>'EMS交运',
			'UPS'=>'UPS',
			'FedEX'=>'FedEX',
			'TNT'=>'TNT',
			'Other'=>'其他',
	);
	
	public static function getCdiscountShippingServices(){
		return self::$cdiscountShippingServices;
	}
	
	public static function getCdiscountBuyerShippingServices(){
		return [
		'STD'=>'标准(Normal/Standard)',//Standard
		'TRK'=>'跟踪(Suivi/Tracked)',//tracking
		'REG'=>'挂号(Recommandé/Registered)',//registered
		'COL'=>'Collissimo',
		'RCO'=>'Relay colis',
		'REL'=>'Mondial Relay',
		'SO1'=>'So Colissimo',
		//'MAG'=>'in shop',
		];
	}
	
	
	//设定priceminister支持的买家可选运输商
	public static $priceministerShippingServices = array(
		'Autre'=>'Autre (*)',
		'Colis Prive'=>'Colis Prive',
		'So Colissimo'=>'So Colissimo',
		'DPD'=>'DPD',
		'Mondial Relay'=>'Mondial Relay',
		'DHL'=>'DHL',
		'UPS'=>'UPS',
		'Fedex'=>'Fedex',
		'TNT'=>'TNT',
		//'Laposte'=>'Laposte',
		'Colissimo'=>'Colissimo',
		'CHRONOPOST'=>'CHRONOPOST',
		'Tatex'=>'Tatex',
		'GLS'=>'GLS',
		'France Express'=>'France Express',
		'Kiala'=>'Kiala (*)',
		'Courrier Suivi'=>'Courrier Suivi',
		'Exapaq'=>'Exapaq'
	);
	
	public static function getPriceministerShippingServices(){
		return self::$priceministerShippingServices;
	}
	
	public static function getPriceministerBuyerShippingServices(){
		return PriceministerOrderHelper::getPriceministerOrderShippingCode();
	}
	
	
	//设定Bonanza支持的买家可选运输商
	public static $bonanzaShippingServices = array(
			'USPS'=>'USPS',
			'UPS'=>'UPS',
			'FedEx'=>'FedEx',
			'International'=>'International',
			'Other'=>'Other'
	);
	
	
	public static function getBonanzaShippingServices(){
		return self::$bonanzaShippingServices;
	}
	
	public static function getBonanzaBuyerShippingServices(){
		return [
		'STD'=>'标准(Normal/Standard)',//Standard
		];
	}
	
	//返回接口类传入的相应操作
	public static function codeToOperate($code)
	{
		$Arr = [
			1=>'getOrderNO',
			2=>'doDispatch',
			3=>'getTrackingNO',
			4=>'doPrint',
			5=>'cancelOrderNO',
			6=>'Recreate'
		];
		return $Arr[$code];
	}
	//判断键值对字符串是否合法
	public static function checkValues($str){
		if(empty($str) || !strpos($str,';') || !strpos($str,':'))return false;
		$params = explode(';',rtrim($str,';'));
		Helper_Array::removeEmpty($params);
		$result = array();
		foreach($params as $v){
			$value = explode(':',$v);
			if(count($value)<2)return false;
			$result[$value[0]] = $value[1];
		}
		return $result;
	}
	
	//按物流商代码删除所有参数
	public static function deleteParams($code){
		$carrier_param = SysCarrierParam::find()->where(['carrier_code'=>$code])->asArray()->all();
        $result = true;
        if($carrier_param && count($carrier_param)>0){
            $result = SysCarrierParam::deleteAll(['carrier_code'=>$code]);
        }
        if(!$result)return false;
        return true;
	}

	//对数据进行转义
	public static function formatData($data){
		if(empty($data))return false;
		$result = array();
		if(is_array($data)){
			foreach($data as $k=>$v){
				if(is_array($v)){
					$result[$k] = self::formatData($v);
					continue;
				}
				$result[$k] = empty($v)?$v:Html::encode($v);
			}
			return $result;
		}
		return Html::encode($result);
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取一个物流商的详细数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param key			用户表中物流商id
	 +----------------------------------------------------------
	 * @return				物流商详细数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/01/30				初始化
	 +----------------------------------------------------------
	 **/
	public static function helpEdit($pk) {
		return SysShippingService::find()->where(['id'=>$pk,'is_used'=>1])->One();
	}

	//返回json格式数组
	protected static function getResult($error = 0, $data=[], $msg) {
		return json_encode(['error'=>$error,'data'=>$data,'msg'=>$msg]);
	}

	/*
	* 生成进度条
	*/
	public static function jindutiao($data,$nums,$current,$ids){
		$str = '';
		//根据传递进来的需要遍历的目录 展示出来
		$str .= '<div id="jindutiao" style="margin-top:20px;margin-bottom:30px"><table ><tr>';
		if(count($data)<1)return '请检查参数';

		$first_label = array_shift($data);
		$first_nums = array_shift($nums);

		$str .= '<td style="background-image:url(/images/carrier/jindutiao_03.png)"><a href="'.Url::toRoute([$first_label['action'],'ids'=>$ids]).'">'.$first_label['name']."(<span id='jindutiao_count0'>{$first_nums}</span>)".'</a></td>';
		$str .= '';

		//初始的深绿色图片颜色
		$current_td = '07';
		$cu_color = '';
		if($current['action'] == $first_label['action']){
			$current_td = '09';
			$cu_color = 'class="othercolor"';
		}
		foreach($data as $k=>$v){
			$str .= '<td style="background-image:url(/images/carrier/jindutiao_'.$current_td.'.png);" '.$cu_color.'><a href="'.Url::toRoute([$v['action'],'ids'=>$ids]).'">'.$v['name']."(<span id='jindutiao_count".($k+1)."'>{$nums[$k]}</span>)".'</a></td>';
			//在每次循环的时候 判断当前进度 从当前进度开始 以后的变成浅绿色
			if($v['action']==$current['action']){
				$current_td = '09';
				$cu_color = 'class="othercolor"';
			}
		}
		$str .= '</tr></table></div>';
		return $str;
	}

	/*
	* 根据具体的操作 返回对应的英文方法
	*/
	static function returnAction($key,$returnAll=false){
		$option = [
			0=>[
				'action'=>'getorderno',
				'name'=>'待上传',
			],
			1=>[
				'action'=>'dodispatch',
				'name'=>'待交运',
			],
			2=>[
				'action'=>'gettrackingno',
				'name'=>'待获取物流号',
			],
			3=>[
				'action'=>'doprint',
				'name'=>'待打印物流单',
			],
			4=>[
				'action'=>'cancelorderno',
				'name'=>'取消订单',
			],
			5=>[
				'action'=>'recreate',
				'name'=>'重新发货',
			],
			6=>[
				'action'=>'finishorder',
				'name'=>'已完成',
			],
		];
		if($returnAll)return $option;
		if(is_array($key)){
			foreach($key as $v){
				$result[] = $option[$v];
			}
			return $result;
		}
		return $option[$key];
	}

	/*
     * 根据order获得接口对象
     * qfl
     */
    public static function Getrequestapi($carrier_code){
        //物流商
        $carrier = SysCarrier::findOne($carrier_code);
        if($carrier == null) return '物流商代码错误';
        if($carrier->carrier_type){
            $class_name = '\common\api\overseaWarehouseAPI\\'.$carrier->api_class;
        }else{
            $class_name = '\common\api\carrierAPI\\'.$carrier->api_class;
        }
        try{
        	if($carrier->api_class == 'LB_RTBCOMPANYCarrierAPI'){
        		//对接软通宝所属物流
        		return new $class_name($carrier->carrier_code);
        	}
        	else{
        		return new $class_name;
        	}
//             return new $class_name;
        }catch(Exception $e){return $e->getMessage();}
    }

	/*
	 * 更新用户库中的运输方式
	 */
	public static function refreshShippingMethod($account,$carrier,$shippingResult = array(),$shipcode=''){
		if ($account->carrier_type==1){
			if(count($account->warehouse)>0)
			foreach ($account->warehouse as $third_party_code){
    				//添加账号成功之后需要将物流服务加到子库中
    				if(empty($shipcode))
    					$shippingObjs = SysShippingMethod::findAll(['carrier_code' => $account->carrier_code ,'third_party_code'=>$third_party_code]);
    				else 
    					$shippingObjs = SysShippingMethod::findAll(['carrier_code' => $account->carrier_code ,'third_party_code'=>$third_party_code,'shipping_method_code'=>$shipcode]);
    				$serviceObjs = SysShippingService::findAll(['carrier_code' => $account->carrier_code,'third_party_code'=>$third_party_code,'carrier_account_id'=>$account->carrier_code]);
    				if ((count($shippingObjs) != count($serviceObjs)) && count($shippingObjs)>0){
    					foreach ($shippingObjs as $shippingObj){
    						$service = SysShippingService::findOne(['carrier_code' => $account->carrier_code,'third_party_code'=>$third_party_code,'carrier_account_id'=>$account->id,'shipping_method_code'=>$shippingObj->shipping_method_code,'is_copy'=>0]);
    						if ($service === null){
    							//is_close 表示该运输方式已经废弃了
    							if($shippingObj->is_close == 1) continue;
    							
    							$service = new SysShippingService();
        						$service->carrier_code =$account->carrier_code;
        						$service->carrier_params =array('third_party_code'=>$shippingObj->third_party_code,'shipping_method_code'=>$shippingObj->shipping_method_code);
        						$service->ship_address ='';
        						$service->return_address ='';
        						$service->is_used =0;
        						$service->service_name =$account->carrier_name.'-'.$shippingObj->shipping_method_name; //$carrier[$account->carrier_code]
        						//$service->service_code =Yii::$app->request->post('service_code');
        						//$service->auto_ship =Yii::$app->request->post('auto_ship');
        						$service->web ="http://www.17track.net";
        						$service->carrier_account_id =$account->id;
        						$service->create_time =time();
        						$service->update_time =time();
        						$service->shipping_method_code =$shippingObj->shipping_method_code;
        						$service->third_party_code =$shippingObj->third_party_code;
        						$service->carrier_name =$carrier[$account->carrier_code];
        						$service->shipping_method_name =$shippingObj->shipping_method_name;
        						$service->warehouse_name = $shippingObj->template;
        						if (!$service->save()){
        							return $service->getErrors();
        						}
    						}else{
    							$service->carrier_name =$carrier[$account->carrier_code];
    							$service->shipping_method_name =$shippingObj->shipping_method_name;
    							$service->third_party_code =$shippingObj->third_party_code;
    							$service->warehouse_name = $shippingObj->template;
    							if (!$service->save()){
        							return $service->getErrors();
    							}
    						}
    					}
    				}
			}
		}else{
			$aipaqiShipping = array();
			if($account->carrier_code == 'lb_aipaqi'){
				if(!empty($shippingResult)){
					if($shippingResult['error'] == 0){
						$aipaqiShipping = self::checkValues($shippingResult['data']);
					}
					
					if($aipaqiShipping === false) return false;
				}
			}

			//添加账号成功之后需要将物流服务加到子库中
			if(empty($shipcode))
				$shippingObjs = SysShippingMethod::findAll(['carrier_code' => $account->carrier_code ]);
			else
				$shippingObjs = SysShippingMethod::findAll(['carrier_code' => $account->carrier_code,'shipping_method_code'=>$shipcode]);
			$serviceObjs = SysShippingService::findAll(['carrier_code' => $account->carrier_code,'carrier_account_id'=>$account->carrier_code]);
			if ((count($shippingObjs) != count($serviceObjs)) && count($shippingObjs)>0){
				foreach ($shippingObjs as $shippingObj){
					$service = SysShippingService::findOne(['carrier_code' => $account->carrier_code,'carrier_account_id'=>$account->id,'shipping_method_code'=>$shippingObj->shipping_method_code,'is_copy'=>0]);
					if ($service === null){
						//is_close 表示该运输方式已经废弃了
						if($shippingObj->is_close == 1) continue;
						if($account->carrier_code == 'lb_aipaqi'){
							//艾帕奇物流比较特殊，特定的账号才能看见特定的运输方式
							if(!isset($aipaqiShipping[$shippingObj->shipping_method_code])){
								continue;
							}
							
						}
						
						$service = new SysShippingService();
						$service->carrier_code =$account->carrier_code;
						$service->carrier_params =array('shipping_method_code'=>$shippingObj->shipping_method_code);
						$service->ship_address ='';
						$service->return_address ='';
						$service->is_used =0;
						$service->service_name =$account->carrier_name.'-'.$shippingObj->shipping_method_name; //$carrier[$account->carrier_code]
						$service->web ="http://www.17track.net";
						$service->carrier_account_id =$account->id;
						$service->create_time =time();
						$service->update_time =time();
						$service->shipping_method_code =$shippingObj->shipping_method_code;
						$service->carrier_name =$carrier[$account->carrier_code];
						$service->shipping_method_name =$shippingObj->shipping_method_name;
						if (!$service->save()){
							return $service->getErrors();
						}
					}else{
						$service->carrier_name =$carrier[$account->carrier_code];
						$service->shipping_method_name =$shippingObj->shipping_method_name;
						if (!$service->save()){
							return $service->getErrors();
						}
					}
				}
			}
		}
	}
	//生成 top menu
	static public function getOrderNav(){
		
		$order_nav_list = [
		'待上传'=>'/carrier/default/waitingpost',
		'待交运'=>'/carrier/default/waitingdelivery',
		'已交运'=>'/carrier/default/deliveryed',
		'已完成'=>'/carrier/default/completed',
		
		];
		$order_nav_active_list = [
		'待上传'=>'waitingpost',
		'待交运'=>'waitingdelivery',
		'已交运'=>'deliveryed',
		'已完成'=>'completed',
		];
	
	
		$NavHtmlStr = '<ul class="main-tab">';
	
		$mappingOrderNav = array_flip($order_nav_active_list);
		foreach($order_nav_list as $label=>$thisUrl){
			$NavActive='';
			if (\yii::$app->controller->action->id == $order_nav_active_list[$label]){
				$NavActive = " active ";
			}
			$NavHtmlStr .= '<li class="'.$NavActive.'"><a href="'.$thisUrl.'">'.TranslateHelper::t($label).'</a></li>';
			/* $NavHtmlStr .= '<div class="pull-left col-md-2">
			 <div class="rectangle-content'.$NavActive.'"><p class="p-rectangle-content'.$NavActive.'"><a href="'.$thisUrl.'">'.TranslateHelper::t($label).'</a></p></div>
			<div class="triangle-right'.$NavActive.'"></div>
			</div>';
			*/
		}
		$NavHtmlStr.='</ul>';
	
	
		return $NavHtmlStr;
	
	}
	/**
	 * 生成ems
	 * @Param:orders=array();
	 * @return:result
	 * ------glp-------2016.2.4
	 */
	static public function Createems($orders){
		$emslist = [];
		$is_searched = [];
		$list = [];
		if($orders):
		//将订单中的id全部获取到，过滤掉重复的
		foreach($orders as $v){
			if(!isset($is_searched[$v->default_shipping_method_code])){
				$shipping_service = SysShippingService::find()->select(['shipping_method_name','carrier_name','print_type'])->where(['id'=>$v->default_shipping_method_code])->one();
				//如果存在没有分配的 则直接过滤掉
				if(is_null($shipping_service))continue;
				$is_searched[$v->default_shipping_method_code] = $shipping_service;
			}
		
			if($shipping_service->print_type == 1)
				$method_name = $is_searched[$v->default_shipping_method_code]['shipping_method_name'].$v->consignee_country_code;
			else
				$method_name = $is_searched[$v->default_shipping_method_code]['shipping_method_name'];
		
			$carrier_name = $is_searched[$v->default_shipping_method_code]['carrier_name'];
			//统计出该运输方式下订单数量
			isset($count_shipping_service[$method_name])?++$count_shipping_service[$method_name]:($count_shipping_service[$method_name] = 1);
			//将订单id根据运输方式分类
			if(!isset($emslist[$method_name]))$emslist[$method_name] = [];
			// $emslist[$method_name] .= $v->order_id.',';
			isset($emslist[$method_name]['order_ids'])?'':$emslist[$method_name]['order_ids'] = '';
			$emslist[$method_name]['order_ids'] .= $v->order_id.',';
			$emslist[$method_name]['display_name'] = $carrier_name.' >>> '.$method_name;
		}
		foreach($emslist as $k=>$v){
			$name = $v['display_name'].' X '.$count_shipping_service[$k];
			$list[$name] = $v['order_ids'];
		}
		endif;
		$result = array();
		$result['emslist']=$list;
		return $result['emslist'];
	}
}
