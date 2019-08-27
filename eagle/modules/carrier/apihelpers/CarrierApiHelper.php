<?php
namespace eagle\modules\carrier\apihelpers;

use yii;
use common\helpers\Helper_Array;
use eagle\modules\carrier\models\SysCarrier;
use eagle\modules\carrier\models\SysShippingService;
use eagle\models\carrier\SysCarrierAccount;
use eagle\modules\carrier\models\MatchingRule;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\catalog\models\ProductTags;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use eagle\modules\dhgate\apihelpers\DhgateApiHelper;
use eagle\modules\util\helpers\RedisHelper;
use common\api\wishinterface\WishInterface_Helper;
use eagle\modules\order\helpers\WishOrderInterface;
use common\api\aliexpressinterface\AliexpressInterface_Helper;
use eagle\modules\carrier\models\SysCarrierCustom;
use eagle\models\SysCountry;
use eagle\models\carrier\CrTemplate;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\models\carrier\CarrierUseRecord;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use yii\db\Query;
use eagle\modules\inventory\helpers\InventoryHelper;
use Qiniu\json_decode;
use common\helpers\Helper_Currency;
use eagle\modules\carrier\helpers\CarrierDeclaredHelper;
use eagle\models\SysShippingMethod;


class CarrierApiHelper{ 
	public static $templateType = array(
			'地址单'=>'地址单',
			'报关单'=>'报关单',
			'配货单'=>'配货单',
			'发票'=>'发票',
			'商品标签'=>'商品标签'
	);
	//参数暂时没用
	public static function getCustomTemplate(){
		$templates = array();
		foreach (self::$templateType as $type){
			$templates[$type]=Helper_Array::toHashmap(CrTemplate::find()->where(['template_type'=>$type])->select(['template_id','template_name'])->asArray()->all(),'template_id','template_name');
		}
		return $templates;
	}
	
	//参数暂时没用
	public static function getCarriers($getall = true,$is_custom=FALSE){
		//只显示用户使用中的物流商
		$useCarrierAccount=SysCarrierAccount::find()->select('carrier_code')->groupBy('carrier_code')->asArray()->all();
		$useCarrierAccount = Helper_Array::getCols($useCarrierAccount,'carrier_code');
		
		if ($is_custom){
			return $carrierCustom = Helper_Array::toHashmap(SysCarrierCustom::find()->select(['carrier_code','carrier_name'])->asArray()->all(), 'carrier_code','carrier_name');
		}
		$carrier = Helper_Array::toHashmap(SysCarrier::find()->select(['carrier_code','carrier_name'])->where(['carrier_code'=>$useCarrierAccount])->asArray()->all(), 'carrier_code','carrier_name');
		$carrierCustom = Helper_Array::toHashmap(SysCarrierCustom::find()->select(['carrier_code','carrier_name'])->asArray()->all(), 'carrier_code','carrier_name');
		return $carrier+$carrierCustom;
	}
	/*
	 * 获取自定义物流商
	 */
	public static function getCustomCarriers(){
		return  Helper_Array::toHashmap(SysCarrierCustom::find()->select(['carrier_code','carrier_name'])->asArray()->all(), 'carrier_code','carrier_name');
	}
	/**
	 * 获取国家
	 * @param string $getall
	 * @return number
	 */
	public static function getcountrys($language='en'){
		if ($language=='en'){
			return Helper_Array::toHashmap(SysCountry::find()->orderBy('country_en')->select(['country_code','country_en'])->asArray()->all(),'country_code','country_en');
		}else if ($language=='zh'){
			return Helper_Array::toHashmap(SysCountry::find()->orderBy('country_en')->select(['country_code','country_zh'])->asArray()->all(),'country_code','country_zh');
		}else if ($language=='en-zh'){
			
		}
	}
	public static function getCarrierAccounts($carrier=NULL){
		return 	Helper_Array::toHashmap(SysCarrierAccount::find()->select(['id','carrier_name'])->asArray()->all(), 'id','carrier_name');
	}
	/**
	 * 
	 * @param string $is_used true获取全部
	 * @param string $is_custom true获取自定物流
	 * @return Ambigous <multitype:, multitype:unknown >
	 */
	public static function getShippingServices($is_used = TRUE,$is_custom = FALSE){
		$queue = SysShippingService::find();
		if ($is_used){
			$queue->where(['is_used'=>1]);
		}
		if ($is_custom){
			$queue->where(['is_custom'=>1]);
		}
		$arr = $queue->select(['id','service_name', 'carrier_code', 'shipping_method_code'])->orderBy('service_name asc')->asArray()->all();
		
		//获取已关闭的运输服务，提示客户该运输服务已关闭
		$sysShippingMethodClose = SysShippingMethod::find()->select('concat(`carrier_code`, `shipping_method_code`, `third_party_code`) code')->where(['is_close'=>1])->andWhere("carrier_code in (select carrier_code from sys_carrier where carrier_type=0)")->asArray()->all();
		if(!empty($sysShippingMethodClose)){
			$sysShippingMethodClose = Helper_Array::toHashmap($sysShippingMethodClose, 'code', 'code');
		}else{
			$sysShippingMethodClose = array();
		}
		foreach ($arr as $key => $shipping_service){
			//屏蔽已弃用的渠道
			if(in_array($shipping_service['carrier_code'].$shipping_service['shipping_method_code'], $sysShippingMethodClose)){
				unset($arr[$key]);
			}
		}
		
		return 	Helper_Array::toHashmap($arr, 'id','service_name');
	}
	/**
	 * 
	 * @param unknown $id 运输服务id
	 * @param unknown $source 订单来源平台
	 * @return 平台标记发货物流代码
	 */
	public static function getServiceCode($id,$source){
		$service = SysShippingService::findOne($id);
		$service_code = $service->service_code;
		if (isset($service_code[$source]) && strlen($service_code[$source])>0){
			return $service_code[$source];
		}else{
			return self::getDefaultServiceCode($source);
		}
		
	}
	
	public static function getServiceUrl($id,$source=''){
		$service = SysShippingService::findOne($id);
		$service_url = $service->web;
		if (!empty($service_url)){
			return $service_url;
		}else{
			return 'http://www.17track.net';
		}
	
	}
	/**
	 * 
	 * @param unknown $source 订单来源平台
	 * @return 平台标记发货默认物流代码
	 */
	public static function getDefaultServiceCode($source){
			$code = 'Other';
			switch ($source){
				case 'ebay':
					$code='Other';
					break;
				case 'amazon':
					$code=AmazonApiHelper::getDefaultShippingCode();
					break;
				case 'aliexpress':
					$code=AliexpressInterface_Helper::getDefaultShippingCode();
					break;
				case 'wish':
					$code=WishOrderInterface::getDefaultShippingCode();
					break;
				case 'dhgate':
					$code=DhgateApiHelper::getDefaultShippingCode();
					break;
				case 'priceminister':
					$code='Autre';
					break;
				default:break;
			}
			return $code;
	}
	/**
	 * @param unknown $code 订单来源平台
	 * @param unknown $source 订单来源平台
	 * @return 平台标记发货默认物流代码
	 */
	public static function getServiceNameEn($code,$source){
		switch ($source){
			case 'ebay':
				$name = $code;
				break;
			case 'amazon':
				$amazon = AmazonApiHelper::getShippingCodeNameMap();
				$name = isset($amazon[$code])?$amazon[$code]:$code;
				break;
			case 'aliexpress':
				$ali = AliexpressInterface_Helper::getShippingCodeNameMap();
				$name = isset($ali[$code])?$ali[$code]:$code;
				break;
			case 'wish':
				$wish = WishOrderInterface::getShippingCodeNameMap();
				$name = isset($wish[$code])?$wish[$code]:$code;
				break;
			case 'dhgate':
				$dhgate = DhgateApiHelper::getShippingCodeNameMap();
				$name = isset($dhgate[$code])?$dhgate[$code]:$code;
				break;
			default:
				$name = $code;
				break;
		}
		return $name;
	}
	/**
	 * 匹配运输服务
	 * $order 订单对象
	 * $reset强制重新匹配
	 * @return 布尔值
	 */
	public static function matchShippingService(&$order,$reset=0, $FullName=''){
		if ($order->default_shipping_method_code=='' || $reset){
			
			$open_carriers = CarrierOpenHelper::getOpenCarrierArr(2, 1);
			
			$open_carriers = array_keys($open_carriers);
			
			$conn=\Yii::$app->subdb;
			
			$queryTmp = new Query;
			$queryTmp->select("a.*,"."`c`.`is_del` as `as_is_del`,`b`.`carrier_code`")
			->from("matching_rule a")
			->leftJoin("sys_shipping_service b", "b.id = a.transportation_service_id")
			->leftJoin("sys_carrier_account c", "c.id = b.carrier_account_id")
			->leftJoin("sys_carrier_custom d", "d.carrier_code = b.carrier_code");
			
			$queryTmp->andWhere('a.created > 0');
			$queryTmp->andWhere(['b.is_used'=>1]);
			$queryTmp->andWhere(['b.is_del'=>0]);
			$queryTmp->andWhere(['a.is_active'=>1]);
			$queryTmp->andWhere(['IFNULL(`c`.`is_used`,`d`.`is_used`)'=>1]);
			
			$warehouses = InventoryApiHelper::getWarehouseIdNameMap();
			
// 			echo $order->default_warehouse_id;
// 			exit;
			
// 			if((count($warehouses) > 1) && ($order->default_warehouse_id != -1) ){
// 				$queryTmp->andWhere("b.proprietary_warehouse like '%\"".$order->default_warehouse_id."\"%' or c.warehouse_id=".$order->default_warehouse_id);
// 			}
			
			$queryTmp->andwhere(['in','b.carrier_code',$open_carriers]);
			
			$sort_arr = array('is_active'=>'is_active desc','priority'=>'priority asc','transportation_service_id'=>'transportation_service_id asc','rule_name'=>'rule_name asc');
			$str = implode(',', $sort_arr);
			
			$queryTmp->orderBy($str);
			
			$matching_ruleArr = $queryTmp->createCommand($conn)->queryAll();
			
			if (count($matching_ruleArr) == 0){
				return false;//没有启用任何规则
			}
			
			
			foreach ($matching_ruleArr as $matching_ruleKey => $matching_ruleVal){
				if($matching_ruleVal['as_is_del'] == 1){
					continue;
				}
					
				$matching_ruleVal['rules'] = unserialize($matching_ruleVal['rules']);
				$matching_ruleVal['source'] = unserialize($matching_ruleVal['source']);
				$matching_ruleVal['site'] = unserialize($matching_ruleVal['site']);
				$matching_ruleVal['selleruserid'] = unserialize($matching_ruleVal['selleruserid']);
				$matching_ruleVal['buyer_transportation_service'] = unserialize($matching_ruleVal['buyer_transportation_service']);
				$matching_ruleVal['receiving_country'] = unserialize($matching_ruleVal['receiving_country']);
				$matching_ruleVal['total_amount'] = unserialize($matching_ruleVal['total_amount']);
				$matching_ruleVal['freight_amount'] = unserialize($matching_ruleVal['freight_amount']);
				$matching_ruleVal['total_weight'] = unserialize($matching_ruleVal['total_weight']);
				$matching_ruleVal['product_tag'] = unserialize($matching_ruleVal['product_tag']);
				$matching_ruleVal['postal_code'] = unserialize($matching_ruleVal['postal_code']);
				$matching_ruleVal['items_location_country'] = unserialize($matching_ruleVal['items_location_country']);
					
				if (self::matching($order,$matching_ruleVal, $FullName)){
					$order->default_carrier_code = $matching_ruleVal['carrier_code'];
					$order->default_shipping_method_code = $matching_ruleVal['transportation_service_id'];
					$order->default_warehouse_id = $matching_ruleVal['proprietary_warehouse_id'];
					
					$order->rule_id = $matching_ruleVal['id'];
					return true;
				}
			}
			
			return false;//没有规则匹配上
		}else{
			return false;//已经有物流服务
		}
	}
	/**
	 * 匹配运输服务
	 * $order 订单对象
	 * $rule 规则
	 * @return 布尔值
	 */
	public static function matching($order,$rule , $FullName=''){
		if (empty($FullName))
			$FullName = $FullName;
		
		//已出库订单补发不支持部分运输服务
		if(!empty($order->order_capture)){
			if(!empty($order->reorder_type)){
				if(($order->order_capture == 'Y') && ($order->reorder_type == 'after_shipment')){
					if(in_array($rule['carrier_code'], array('lb_epacket','lb_ebaytnt','lb_ebayubi'))){
						OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':'."已出库订单补发不能匹配该服务",$FullName);
						return false;
					}
				}
			}
		}
		
		if (is_array($rule['rules']) && count($rule['rules'])){
			foreach ($rule['rules'] as $type){
				if ($type == 'sources'){//平台、账号、站点
					if(is_array($rule['source']) && count($rule['source']) > 0){
						if (!in_array($order->order_source, $rule['source'])){
							OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':订单来源'.$order->order_source."未满足条件",$FullName);
							return false;
						}
						
						if((is_array($rule['site'])) && (count($rule['site']) > 0) && (in_array($order->order_source, $rule['source']))){
							$site = $rule['site'];
							if (isset($site[$order->order_source]) && count($site[$order->order_source])>0 ){
								if (!in_array($order->order_source_site_id, $site[$order->order_source])){
									OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':站点'.$order->order_source_site_id."未满足条件",$FullName);
									return false;
								}
							}else{
								OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':站点'.$order->order_source_site_id."未满足条件",$FullName);
								return false;
							}
						}
							
						if((is_array($rule['selleruserid'])) && (count($rule['selleruserid']) > 0) && (in_array($order->order_source, $rule['source']))){
							$selleruserid = $rule['selleruserid'];
							if ( isset($selleruserid[$order->order_source]) && count($selleruserid[$order->order_source])>0 ){
								if (!in_array($order->selleruserid, $selleruserid[$order->order_source])){
									OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':卖家账号'.$order->selleruserid."未满足条件",$FullName);
									return false;
								}
							}else {
								OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':卖家账号'.$order->selleruserid."未满足条件",$FullName);
								return false;
							}
						}
					}
				}else if($type == 'buyer_transportation_service'){//买家选择运输服务
					$buyer_transportation_service = $rule['buyer_transportation_service'];
					//速卖通的买家选择运输服务，没有站点的概念，而且他的买家选择运输服务是记录在od_order_v2 表的addi_info的json key=>shipping_service 
					if($order->order_source == 'aliexpress'){
						if (isset($buyer_transportation_service[$order->order_source]) && count($buyer_transportation_service[$order->order_source])>0 ){
							
							$tmpaddi_info_service = empty(json_decode($order->addi_info,true)['shipping_service']) ? array() : json_decode($order->addi_info,true)['shipping_service'];
							
							if(!empty($tmpaddi_info_service)){
								$tmpaddi_info_service_continue = array();
								
								foreach ($tmpaddi_info_service as $tmpaddi_info_service_val){
									$tmpaddi_info_service_continue[$tmpaddi_info_service_val] = $tmpaddi_info_service_val;
								}
								
								//这里假如客选物流存在多个的时候直接跳出判断，由客户自己判断
								if(count($tmpaddi_info_service_continue) > 1){
									OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':客选物流存在多个,请自行选择该订单的物流方式'.$order->order_source_shipping_method."未满足条件",$FullName);
									return false;
								}
								
								unset($tmpaddi_info_service_continue);
								$tmpaddi_info_service_continue = array();
								
								foreach ($tmpaddi_info_service as $tmpaddi_info_service_val){
									if (!in_array($tmpaddi_info_service_val, $buyer_transportation_service[$order->order_source])){
										$tmpaddi_info_service_continue[] = true;
									}else{
										$tmpaddi_info_service_continue[] = false;
									}
								}
								
								if(!empty($tmpaddi_info_service_continue)){
									if(!in_array(false, $tmpaddi_info_service_continue)){
										OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':客选物流'.$order->order_source_shipping_method."未满足条件",$FullName);
										return false;
									}
								}
							}else{
								OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':客选物流'.$order->order_source_shipping_method."未满足条件e2",$FullName);
								return false;
							}
						}else{
							OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':客选物流'.$order->order_source_shipping_method."未满足条件e1",$FullName);
							return false;
						}
					}else if($order->order_source == 'ebay'){
						if (isset($buyer_transportation_service[$order->order_source][$order->order_source_site_id]) && count($buyer_transportation_service[$order->order_source][$order->order_source_site_id])>0 ){
							if (!in_array($order->order_source_shipping_method, $buyer_transportation_service[$order->order_source][$order->order_source_site_id])){
								OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':客选物流'.$order->order_source_shipping_method."未满足条件",$FullName);
								return false;
							}
						}else{
							OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':客选物流'.$order->order_source_shipping_method."未满足条件",$FullName);
							return false;
						}
					}else{
						if (isset($buyer_transportation_service[$order->order_source]) && count($buyer_transportation_service[$order->order_source])>0 ){
							if (!in_array($order->order_source_shipping_method, $buyer_transportation_service[$order->order_source])){
								OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':客选物流'.$order->order_source_shipping_method."未满足条件",$FullName);
								return false;
							}
						}else{
							OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':客选物流'.$order->order_source_shipping_method."未满足条件",$FullName);
							return false;
						}
					}
				}else if($type == 'receiving_country'){//收件国家
					if (!in_array($order->consignee_country_code, $rule['receiving_country'])){
						OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':收件国家'.$order->consignee_country_code."未满足条件",$FullName);
						return false;
					}
				}else if($type == 'total_amount'){
					$total_min_max=$rule['total_amount'];
					$tmp_grand_total = Helper_Currency::convertThisCurrencyToUSD($order->currency, $order->grand_total);
					
					if (!($tmp_grand_total >= $total_min_max['min'] && $tmp_grand_total <$total_min_max['max'])){
						OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':总金额:'.$order->grand_total.',USD'.$tmp_grand_total."未满足条件",$FullName);
						return false;
					}
				}else if($type == 'total_amount_new'){//订单总金额
					$tmp_total_amount_new = json_decode($rule['total_amount_new'], true);
					$is_currency_dissatisfy = false;	//是否不满足条件
					
					if(!empty($order->currency)){
						if(isset($tmp_total_amount_new[$order->currency])){
							$tmp_total_amount_new_min = '';
							$tmp_total_amount_new_max = '';
							if(!empty($tmp_total_amount_new[$order->currency]['min'])){
								$tmp_total_amount_new_min = $tmp_total_amount_new[$order->currency]['min'];
							}
							
							if(!empty($tmp_total_amount_new[$order->currency]['max'])){
								$tmp_total_amount_new_max = $tmp_total_amount_new[$order->currency]['max'];
							}
							
							if(!empty($tmp_total_amount_new_min) && empty($tmp_total_amount_new_max)){
								if (!($order->grand_total >= $tmp_total_amount_new_min)){
									$is_currency_dissatisfy = true;
								}
							}else if(empty($tmp_total_amount_new_min) && !empty($tmp_total_amount_new_max)){
								if (!($order->grand_total <= $tmp_total_amount_new_max)){
									$is_currency_dissatisfy = true;
								}
							}else if(!empty($tmp_total_amount_new_min) && !empty($tmp_total_amount_new_max)){
								if (!($order->grand_total >= $tmp_total_amount_new_min && $order->grand_total <= $tmp_total_amount_new_max)){
									$is_currency_dissatisfy = true;
								}
							}
						}else{
							$is_currency_dissatisfy = true;
						}
					}else{
						$is_currency_dissatisfy = true;
					}
					
					if($is_currency_dissatisfy == true){
						OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':订单总金额:'.$order->grand_total.','.$order->currency."未满足条件",$FullName);
						return false;
					}
				}else if($type == 'freight_amount'){
					$freight_min_max=$rule['freight_amount'];
					if (!($order->shipping_cost >= $freight_min_max['min'] && $order->shipping_cost < $freight_min_max['max'])){
						OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':买家支付运费'.$order->shipping_cost."未满足条件",$FullName);
						return false;
					}
				}else if($type == 'total_weight'){
					$total_weight = $rule['total_weight'];
					$weight = 0;
					//商品重量相加
					foreach ($order->items as $item){
						if (!empty($item->root_sku)){//判断sku是否存在
							$skus = ProductApiHelper::getSkuInfo($item->root_sku, $item->quantity);
							foreach ($skus as $productInfo){
								if (isset($productInfo['prod_weight'])){
									$weight += $productInfo['prod_weight']*$productInfo['qty'];
								}
							}
						}
					}
					if (!($weight >= $total_weight['min'] && $weight < $total_weight['max'])){
						OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':总重量'.$weight."g未满足条件",$FullName);
						return false;
					}
				}else if($type == 'product_tag'){
					$product_tag = $rule['product_tag'];
					$tmpItems = array();
					$tmpItemsErr = '';
						
					foreach ($order->items as $item){
						if(($order->order_source=='cdiscount') && in_array($item->sku, CdiscountOrderInterface::getNonDeliverySku()))
							continue;
						
						if(isset($tmpItems[$item->sku])){
							continue;
						}else{
							$tmpItems[$item->sku] = true;
						}
					
						//是否有电池判断
						if (!empty($item->root_sku)){
							$skus = ProductApiHelper::getSkuInfo($item->root_sku, $item->quantity);
							//查询只要订单包含的商品中有一个商品的标签匹配上就直接匹配
							foreach ($skus as $productInfo){
								$tags_arr = ProductTags::find()->where(['sku'=>$productInfo['sku']])->asArray()->all();
								if (count($tags_arr)>0){//有标签
									$tags = Helper_Array::getCols($tags_arr,'tag_id');
									if ($productInfo['battery'] =='Y'){//是否有电池
										$tags[]='battery';
									}
										
									//比较是否有相同值
									$same_value= array_intersect($tags, $product_tag);
									//$same_value没有值说明sku的标签不在规则所选的标签内，没有匹配上，返回false
									if (count($same_value)==0){
										$tmpItemsErr = $rule['rule_name'].':商品标签'.implode(',', $tags)."未满足条件.eor_1";
										$tmpItems[$item->sku] = false;
									}
								}else{
									//没有商品标签肯定的匹配不上直接返回false
									$tmpItemsErr = $rule['rule_name'].':商品标签空,未满足条件.eor_2';
									$tmpItems[$item->sku] = false;
								}
							}
						}else{
							{
								$tmpItemsErr = $rule['rule_name'].':商品标签,SKU:'.$item->sku.'未找到.eor_4';
								$tmpItems[$item->sku] = false;
							}
						}
					}
						
					if(!in_array(true, $tmpItems)){
						OperationLogHelper::log('order',$order->order_id,'分配运输服务',$tmpItemsErr,$FullName);
						return false;
					}
				}else if($type == 'postal_code'){
				    $success = 0;
				    $tmpItemsErr = '';
				    $consignee_postal_code = $order->consignee_postal_code;
				    foreach($rule['postal_code'] as $v){
				    	$postal_code = explode(',',$v);
				    	$type = $postal_code[0];
				    	if(!empty($type)){
				    		if($type == 'type_start'){
				    		    if(count($postal_code) == 2){
				    		        if(substr($consignee_postal_code, 0, strlen($postal_code[1])) == $postal_code[1]){
				    		            $success = 1;
				    		            break;
				    		        }
				    		    }
				    		}
				    		else if($type == 'type_contains'){
				    		    if(count($postal_code) == 2){
				    		        if(strpos($consignee_postal_code, $postal_code[1]) !== false){
				    		            $success = 1;
				    		            break;
				    		        }
				    		    }
				    		}
				    		else if($type == 'type_start_contains'){
				    		    if(count($postal_code) == 3){
				    		        if(substr($consignee_postal_code, 0, strlen($postal_code[1])) == $postal_code[1]){
				    		            if(strpos($consignee_postal_code, $postal_code[2]) !== false){
				    		            	$success = 1;
				    		            	break;
				    		            }
				    		        }
				    		    }
				    		}
				    		else if($type == 'type_range'){
				    		    if(count($postal_code) == 4){
				    		        $val = substr($consignee_postal_code, 0, $postal_code[1]);
				    		        if($val >= $postal_code[2] && $val <= $postal_code[3]){
				    		            $success = 1;
				    		            break;
				    		        }
				    		    }
				    		}
				    	}
				    }
					if(!$success){
						OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].': 收件人邮编 '.$consignee_postal_code.' 未满足条件',$FullName);
						return false;
					}
				}else if($type == 'receiving_provinces'){
					if(!empty($rule['receiving_provinces'])){
						$rule['receiving_provinces'] = self::replaceNewline($rule['receiving_provinces']);
						
						$tmp_receiving_provinces = explode(",",$rule['receiving_provinces']);
							
						if(is_array($tmp_receiving_provinces)){
							//将数组里面的英文转为大写
							$tmp_receiving_provinces = array_flip(array_change_key_case(array_flip($tmp_receiving_provinces),CASE_UPPER));
					
							if (!in_array(strtoupper($order->consignee_province), $tmp_receiving_provinces ,false)){
								OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':收件人州/省份'.$order->consignee_province."未满足条件",$FullName);
								return false;
							}
						}
					}
				}else if($type == 'receiving_city'){
					if(!empty($rule['receiving_city'])){
						$rule['receiving_city'] = self::replaceNewline($rule['receiving_city']);
						
						$tmp_receiving_city = explode(",",$rule['receiving_city']);
							
						if(is_array($tmp_receiving_city)){
							$tmp_receiving_city = array_flip(array_change_key_case(array_flip($tmp_receiving_city),CASE_UPPER));
					
							if (!in_array(strtoupper($order->consignee_city), $tmp_receiving_city)){
								OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':收件人城市'.$order->consignee_city."未满足条件",$FullName);
								return false;
							}
						}
					}
				}else if($type == 'skus'){
					if(!empty($rule['skus'])){
						$tmp_skus = json_decode($rule['skus'], true);
							
						if(is_array($tmp_skus)){
							$tmp_skus = array_flip(array_change_key_case(array_flip($tmp_skus),CASE_UPPER));
							
							foreach ($order->items as $item){
								if($order->order_source=='cdiscount' && in_array($item->sku, CdiscountOrderInterface::getNonDeliverySku()))
									continue;
								
								//查询对应的root sku
// 								$root_sku = ProductApiHelper::getRootSKUByAlias($item->sku);
								
								//以后都使用ROOT SKU 2017-04-05
								$root_sku = $item->root_sku;
								
								if($root_sku == ''){
// 									$root_sku = $item->sku;
									
									OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':没有配对 SKU:'."",$FullName);
									return false;
								}
								
								if(!in_array(strtoupper($root_sku), $tmp_skus)){
									OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':配对 SKU:'.$root_sku."未满足条件",$FullName);
									return false;
								}
							}
						}
					}
				}else if($order->order_source == 'ebay'){
					$sourceItemids = array();
					
					foreach ($order->items as $item){
						$sourceItemids[$item->order_source_itemid] = $item->order_source_itemid;
					}
					
					if(!empty($sourceItemids)){
						if($type == 'items_location_country'){
							if(isset($rule['items_location_country']) && count($rule['items_location_country'])>0){
								if(InventoryHelper::ebayItemsMatchWarehouse($order, $sourceItemids, 'items_location_country', $rule['items_location_country']) == false){
									OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':物品所在国家:'."未满足条件",$FullName);
									return false;
								}
							}
						}else if($type == 'items_location_provinces'){
							if(!empty($rule['items_location_provinces'])){
								$tmp_items_location_provinces = json_decode($rule['items_location_provinces'], true);
								
								if(is_array($tmp_items_location_provinces)){
									$tmp_items_location_provinces = array_flip(array_change_key_case(array_flip($tmp_items_location_provinces),CASE_UPPER));
									if(InventoryHelper::ebayItemsMatchWarehouse($order, $sourceItemids, 'items_location_provinces', $tmp_items_location_provinces) == false){
										OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':物品所在地区:'."未满足条件",$FullName);
										return false;
									}
								}
							}
						}
					}
				}
			}
			
			//通过了所有匹配项
			OperationLogHelper::log('order',$order->order_id,'分配运输服务',$rule['rule_name'].':匹配成功',$FullName);
			return true;
		}
	}
	
	/**
	 * +----------------------------------------------------------
	 * 获取SKU对应商品信息和和仓库配货信息
	 * +----------------------------------------------------------
	 * @param
	 *        	sku 待检测的商品 SKU/alias
	 *+----------------------------------------------------------
	 * @return boolean true 为有对应的商品  , false 为没有对应 的商品
	 *+----------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh 	2015/04/30				初始化
	 *+----------------------------------------------------------
	 * @todo
	 */
	/**
	 * +----------------------------------------------------------
	 * 返回报关数据，
	 * +----------------------------------------------------------
	 * @param
	 * $product(数组) 			商品信息
	 * $order(对象)				订单
	 * $getChildren				是否获取子商品
	 * 
	 * $shippingService 和 $shipping_service 理论上一样，但是由于前期某些function没有传递$shippingService所以后面需要用到$shipping_service
	 *+----------------------------------------------------------
	 * @return 
	 * array(
	 * 
	 * )
	 *+----------------------------------------------------------
	 * log			name	date					note
	 * @author 		million 	2015/07/20				初始化
	 *+----------------------------------------------------------
	 */
	public static function getDeclarationInfo($order,$shippingService,$getChildren=true,$getWearhouse=false, $shipping_service = null){
		$tmp_max_declared_value = 0;
		if($shipping_service != null){
			if(!empty($shipping_service['declaration_max_value'])){
				$tmp_max_declared_value = $shipping_service['declaration_max_value'];
			}
		}
		
// 		$tmpCommonDeclaredInfo = \eagle\modules\carrier\helpers\CarrierOpenHelper::getCommonDeclaredInfoByDefault();
		
		//批量订单获取相关报关信息
		$order_items_info = array();
		
		foreach($order->items as $item){
			$tmp_platform_itme_id = \eagle\modules\order\helpers\OrderGetDataHelper::getOrderItemSouceItemID($order->order_source , $item);
			$order_items_info[] = array('platform_type'=>$order->order_source, 'order_status'=>$order->order_status, 'xlb_item'=>$item->order_item_id,'sku'=>$item->sku, 'root_sku'=>$item->root_sku, 'itemID'=>$tmp_platform_itme_id, 'declaration'=>json_decode($item->declaration,true));
		}
		
		//统一获取报关信息
		$result_item_declared_info = CarrierDeclaredHelper::getOrderDeclaredInfoBatch($order_items_info);
		
		$products = array();
		$total = 0;
		$currency = $order->currency;
		$total_price = 0;
		$total_weight = 0;//克
		$has_battery = false;
		$warehouse = Warehouse::findOne(['warehouse_id'=>$order->default_warehouse_id]);
		if ($warehouse == null){
			$warehouse = new Warehouse();
		}
		//default_shipping_method_code
		foreach($order->items as $item){
			if($item->delivery_status == 'ban') continue;
			
			//cd发货时不要传item中的NonDeliverySku   //liang 2015-11-17
			if(strtolower($order->order_source)=='cdiscount'){
				if(in_array($item->sku, CdiscountOrderInterface::getNonDeliverySku())){
					continue;
				}
			}
			
// 			if(empty($item->sku)){
// 				$tmp_sku_1 = $item->product_name;
// 			}else{
// 				$tmp_sku_1 = $item->sku;
// 			}
			$tmp_sku_1 = $item->root_sku;
			
			$tmp_one_item_declared = $result_item_declared_info[$item->order_item_id]['declaration'];
			
			$product = ProductApiHelper::getProductInfo($tmp_sku_1);
			if($product ==null){//sku不存在的
				$product = array(
						'name'=>$item->product_name,
						'photo_primary'=>$item->photo_primary,
						'declaration_ch'=>$tmp_one_item_declared['nameCN'],
						'declaration_en'=>$tmp_one_item_declared['nameEN'],
						'declaration_value'=>$tmp_one_item_declared['price'],
						'total_price'=> $tmp_one_item_declared['price']*$item->quantity,
						'declaration_value_currency'=>$order->currency,
						'prod_weight'=>$tmp_one_item_declared['weight'],
						'total_weight'=>$tmp_one_item_declared['weight']*$item->quantity,
						'battery'=>'N',
						'note'=>'',
						'quantity'=>$item->quantity,
						'sku'=>$item->sku,
						'product_attributes'=>$item->product_attributes,
						'transactionid'=>$item->order_source_transactionid,//交易号
						'itemid'=>$item->order_source_itemid,//在线商品的刊登号或商品号
						'warehouse'=>$warehouse->name,//仓库
						'location_grid'=>'无仓位',//仓位
						'purchase_by'=>'',
						'prod_name_ch'=>'',
						'prod_name_en'=>'',
				        'pro_width'=>'',
				        'pro_length'=>'',
				        'pro_height'=>'',
				);
				$total+=$product['quantity'];
				$total_price+=$product['total_price'];
				$total_weight+=$product['total_weight'];
				$products[] = $product;
			}else{//sku存在
				//是否要获取子商品
				if ($getChildren && isset($product['children']) && count($product['children'])>0){
					foreach ($product['children'] as $row){
						$inventory = InventoryApiHelper::getPickingInfo(array($row['sku']),$order->default_warehouse_id);
						$product = array(
								'name'=>$row['name'],
								'photo_primary'=>$row['photo_primary'],
								'declaration_ch'=>$tmp_one_item_declared['nameCN'],
								'declaration_en'=>$tmp_one_item_declared['nameEN'],
								'declaration_value'=>$tmp_one_item_declared['price'],
								'total_price'=>$tmp_one_item_declared['price']*$row['qty'],
								'declaration_value_currency'=>strlen($row['declaration_value_currency'])>0?$row['declaration_value_currency']:'USD',
								'prod_weight'=>$tmp_one_item_declared['weight'],
								'total_weight'=>$tmp_one_item_declared['weight']*$row['qty'],
								'battery'=>strlen($row['battery'])>0?$row['battery']:'N',
								'note'=>'',
								'quantity'=>$item->quantity*$row['qty'],
								'sku'=>$row['sku'],
								'product_attributes'=>$item->product_attributes,
								'transactionid'=>$item->order_source_transactionid,//交易号
								'itemid'=>$item->order_source_itemid,//在线商品的刊登号或商品号
								'warehouse'=>$warehouse->name,//仓库
								'location_grid'=>isset($inventory[0]['location_grid'])?$inventory[0]['location_grid']:'无',//货位
								'purchase_by'=>$row['purchase_by'],
								'prod_name_ch'=>$row['prod_name_ch'],
								'prod_name_en'=>$row['prod_name_en'],
    						    'pro_width'=>$row['prod_width'],
    						    'pro_length'=>$row['prod_length'],
    						    'pro_height'=>$row['prod_height'],
						);
						if ($has_battery==false && $row['battery']=="Y"){
							$has_battery = true;
						}
						$total+=$product['quantity'];
						$total_price+=$product['total_price'];
						$total_weight+=$product['total_weight'];
						$products[] = $product;
					}
					
				}else{
					$inventory = InventoryApiHelper::getPickingInfo(array($product['sku']),$order->default_warehouse_id);
					$product = array(
							'name'=>$product['name'],
							'photo_primary'=>$product['photo_primary'],
							'declaration_ch'=>$tmp_one_item_declared['nameCN'],
							'declaration_en'=>$tmp_one_item_declared['nameEN'],
							'declaration_value'=>$tmp_one_item_declared['price'],
							'total_price'=>$tmp_one_item_declared['price']*$item->quantity,
							'declaration_value_currency'=>strlen($product['declaration_value_currency'])>0?$product['declaration_value_currency']:'USD',
							'prod_weight'=>$tmp_one_item_declared['weight'],
							'total_weight'=>$tmp_one_item_declared['weight']*$item->quantity,
							'battery'=>strlen($product['battery'])>0?$product['battery']:'N',
							'note'=>'',
							'quantity'=>$item->quantity,
							'sku'=>$product['sku'],
							'product_attributes'=>$item->product_attributes,
							'transactionid'=>$item->order_source_transactionid,//交易号
							'itemid'=>$item->order_source_itemid,//在线商品的刊登号或商品号
							'warehouse'=>$warehouse->name,//仓库
							'location_grid'=>isset($inventory[0]['location_grid'])?$inventory[0]['location_grid']:'无',//货位
							'purchase_by'=>$product['purchase_by'],
							'prod_name_ch'=>$product['prod_name_ch'],
							'prod_name_en'=>$product['prod_name_en'],
    					    'pro_width'=>$product['prod_width'],
    					    'pro_length'=>$product['prod_length'],
    					    'pro_height'=>$product['prod_height'],
					);
					$total+=$product['quantity'];
					$total_price+=$product['total_price'];
					$total_weight+=$product['total_weight'];
					$products[] = $product;
				}
			}
			
		}
		
		//控制最大报关价值
		if(!empty($tmp_max_declared_value)){
			if(($total_price > $tmp_max_declared_value) && ($total_price > 0) && ($tmp_max_declared_value > 0)){
				$sum_total_price = $total_price;
				$total_price = 0;
		
				foreach ($products as $productsKey => $productsVal){
					$tmp_percentum = $tmp_max_declared_value / $sum_total_price;
// 					$tmp_percentum = round($tmp_percentum, 2);
						
					$products[$productsKey]['declaration_value'] = round(($products[$productsKey]['declaration_value'] * $tmp_percentum), 2);
					$products[$productsKey]['total_price'] = $products[$productsKey]['quantity'] * $products[$productsKey]['declaration_value'];
						
					$total_price+=$products[$productsKey]['total_price'];
				}
			}
		}
		
		return array(
				'total'=>$total,
				'currency'=>$currency,
				'total_price'=>$total_price,
				'total_weight'=>$total_weight,//克
				'has_battery'=>$has_battery,
				'products'=>$products,
		);
	}
	/**
	 * 获取物流商列表
	 * @param $type	物流商类型		0:货代	1:海外仓		2:海外仓+货代		
	 *							3:自定义物流的Excel类型		4:自定义物流的分配跟踪号类型		5:自定义物流	
	 *							6:海外仓+货代+自定义物流
	 * @param $is_use 开启状态	empty/0:开启过但处于关闭状态		1:开启状态	2:未曾开启过的关闭状态		3:关闭状态		else:全部
	 * @return string[]
	 * 
	 * @author zwd 2016/1/28
	 */
	public static function getCarrierList($type=0,$is_use=-1){
		$carrierList = [];$carrierCustomList=[];
		//获取货代/海外仓/海外仓+货代/全部
		if($type == 0 || $type == 1 || $type == 2 || $type == 6){
			//如果需要获取正在使用的，或者关闭状态的
			if($is_use == 0 || $is_use == 1 || $is_use == 2 || $is_use == 3){
				$useCarrier = CarrierUseRecord::find()->select('carrier_code')->where(['is_del'=>'0']);
				if($is_use == 0 || $is_use == 1) $useCarrier->andWhere(['is_active'=>$is_use]);
				if($is_use == 3) $useCarrier->andWhere(['is_active'=>1]);
				$useCarrierList = $useCarrier->asArray()->all();
				$useCarrierList = Helper_Array::getCols($useCarrierList,'carrier_code');
			}
			$carrierList = SysCarrier::find()->select(['carrier_code','carrier_name']);
			if(empty($type) || $type == 1){
				$carrierList->andWhere(['carrier_type'=>$type]);
			}
			if($is_use == 0 || $is_use == 1) $carrierList->andWhere(['carrier_code'=>$useCarrierList]);
			if($is_use == 2) $carrierList->andWhere(['not in','carrier_code',$useCarrierList]);
			if($is_use == 3) $carrierList->andWhere(['not in','carrier_code',$useCarrierList]);
			$carrierList = $carrierList->orderBy('carrier_name asc')->asArray()->all();
			$carrierList = Helper_Array::toHashmap($carrierList, 'carrier_code','carrier_name');
		}
		//自定义物流的Excel类型/自定义物流的分配跟踪号类型/自定义物流/全部
		if($type == 3 || $type == 4 || $type == 5 || $type == 6){
			$customCarrier = SysCarrierCustom::find()->select(['carrier_code','carrier_name']);
			if($type == 3) $customCarrier->where(['carrier_type'=>1])->andWhere(['warehouse_id'=>-1]);//Excel类型
			if($type == 4) $customCarrier->where(['carrier_type'=>0]);//分配跟踪号类型
			if(empty($is_use) || $is_use == 1){
				$customCarrier->andWhere(['is_used'=>$is_use]);
			}
			if($is_use == 2 || $is_use == 3) $customCarrier->andWhere(['is_active'=>0]);
			$customCarrier = $customCarrier->orderBy('carrier_name asc')->asArray()->all();
			$carrierCustomList = Helper_Array::toHashmap($customCarrier, 'carrier_code','carrier_name');
		}
		
		return $carrierList+$carrierCustomList;
	}
	/**
	 * 获取运输服务列表
	 * @param int $is_used 开启状态	1:开启状态	0:关闭状态	else:全部
	 * @param int $carrier_type 对应的物流类型	0：海外仓+货代	1：excel		2：跟踪号		3:自定义运输服务（excel+跟踪号）	4:货代	5:海外仓	else:全部
	 * @param bool $check_carrier_open 是否检测对应物流商是否已开启	1:检测	0:无视
	 * @return string[]
	 * 
	 * @author zwd 2016/1/28
	 */
	public static function getShippingServiceList($is_used=1,$carrier_type=-1,$check_carrier_open=1){
		$queue = SysShippingService::find();
		if ($is_used == 0 || $is_used == 1){
			$queue->where(['is_used'=>$is_used]);
		}
		$check = ($check_carrier_open)?1:-1;
		if($carrier_type == 0){
			$carrer_list = array_keys(self::getCarrierList(2,$check));
			$queue->andWhere(['carrier_code'=>$carrer_list]);
		}
		else if($carrier_type == 1){
			$carrer_list = array_keys(self::getCarrierList(3,$check));
			$queue->andWhere(['carrier_code'=>$carrer_list]);
		}
		else if($carrier_type == 2){
			$carrer_list = array_keys(self::getCarrierList(4,$check));
			$queue->andWhere(['carrier_code'=>$carrer_list]);
		}
		else if($carrier_type == 3){
			$carrer_list = array_keys(self::getCarrierList(5,$check));
			$queue->andWhere(['carrier_code'=>$carrer_list]);
		}
		else if($carrier_type == 4){
			$carrer_list = array_keys(self::getCarrierList(0,$check));
			$queue->andWhere(['carrier_code'=>$carrer_list]);
		}
		else if($carrier_type == 5){
			$carrer_list = array_keys(self::getCarrierList(1,$check));
			$queue->andWhere(['carrier_code'=>$carrer_list]);
		}
		$arr = $queue->select(['id','service_name'])->orderBy('service_name asc')->asArray()->all();
		
		return 	Helper_Array::toHashmap($arr, 'id','service_name');
	}
	/**
	 * 获取匹配规则的详细信息
	 * @param	$defaultPageSize	每页显示多小条记录
	 * @param	$is_active			是否可用 	1:可用	0:不可用	else:全部
	 */
	public static function getMatchingRuleList($defaultPageSize=15, $params = array(),$is_active = -1){
		return CarrierOpenHelper::getMatchingRuleList($defaultPageSize, $params,$is_active);
	}
	
	/**
	 * 将换行符替换空格
	 * 
	 * @param $str
	 * @return string
	 */
	public static function replaceNewline($str){
		$replaceArr=array("\t","\n","\r");
		$replaceAfterArr=array("","","");
		return str_replace($replaceArr, $replaceAfterArr, $str);
	}
	
	/**
	 * 获取运输服务新的方法把删除的不显示
	 */
	public static function getShippingServices2_1(){
		$result = CarrierOpenHelper::getSysShippingMethodList('',6,true,1);
		return $result['response']['data'];
	}
	
	
}