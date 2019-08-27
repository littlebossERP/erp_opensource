<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\order\helpers;

use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\catalog\models\Product;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\catalog\models\ProductSuppliers;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\ImageCacherHelper;
use eagle\modules\listing\models\CdiscountOfferList;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;
use eagle\modules\catalog\models\ProductBundleRelationship;

class OrderProfitHelper{
	
	
	protected static $EXCEL_PRODUCT_COST_COLUMN_MAPPING = array (
			"A" => "sku", //SKU
			"B" => "purchase_price", // 采购价
			"C" => "additional_cost", // 其他费用
	);
	
	protected static $EXCEL_ORDER_LOGISTICS_COST_COLUMN_MAPPING = array (
			"A" => "order_number", //原始订单号
			"B" => "track_no",//物流号
			"C" => "order_source", // 来源平台
			"D" => "logistics_cost", // 物流成本
			"E" => "logistics_weight", // 包裹重量
	);
	
	/**
	 * +----------------------------------------------------------
	 * 导入商品成本的映射关系
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @return 导入商品成本的映射关系
	 * +----------------------------------------------------------
	 * log			name		date			note
	 * @author 		lzhl		2016/03/15		初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function get_EXCEL_PRODUCT_COST_COLUMN_MAPPING(){
		return self::$EXCEL_PRODUCT_COST_COLUMN_MAPPING;
	}//end of get_EXCEL_PRODUCT_COST_COLUMN_MAPPING
	
	/**
	 * +----------------------------------------------------------
	 * 导入定点杆物流成本的映射关系
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @return 导入定点杆物流成本的映射关系
	 * +----------------------------------------------------------
	 * log			name		date			note
	 * @author 		lzhl		2016/03/15		初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function get_EXCEL_ORDER_LOGISTICS_COST_COLUMN_MAPPING(){
		return self::$EXCEL_ORDER_LOGISTICS_COST_COLUMN_MAPPING;
	}//end of get_EXCEL_ORDER_LOGISTICS_COST_COLUMN_MAPPING
	
	
	//scene 1 :使用pd_product的采购价计算利润
	public static function checkOrdersBeforProfit($order_ids=[]){
		$uid = \Yii::$app->user->id;
		$rtn['success'] = true;
		$rtn['message'] = "";
		$rtn['data'] = [];
		$id_arr = [];
		foreach ($order_ids as $order_id){
			if(trim($order_id)!=='')
				$id_arr[] = trim($order_id);
		}
		if(!empty($id_arr)){
			//@todo	
			//nonDeliverySku,暂时只有Cdiscount平台有。
			$nonDeliverySku = CdiscountOrderInterface::getNonDeliverySku();
			
			$items = OdOrderItem::find()->where(['order_id'=>$id_arr])->orderBy("purchase_price_to ASC , order_item_id ASC")->asArray()->all();
			$skus = [];
			$order_itemids_info = [];
			foreach ($items as $item){
				$order_itemids_info[$item['sku']]['source_itemid'] = $item['order_source_itemid'];
				if(in_array(strtoupper($item['sku']),$nonDeliverySku))
					continue;
				if(!in_array($item['sku'], $skus))
					$skus[] = $item['sku'];
				
				$rtn['data']['need_set_price'][$item['sku']]['price_based_on_order'] = $item['purchase_price'];
			}
			//列出需要设置采购价 OR 其他成本 的商品
			if(empty($skus)){//异常
				$rtn['success'] = false;
				$rtn['message'] = "订单商品详情丢失";
				return $rtn;
			}else {
				$pds = Product::find()->where(['sku'=>$skus])->asArray()->all();
				//未在商品模块建立的商品的sku
				$sku_not_pd=[];
				foreach ($skus as $sku){
					$sku_not_pd[$sku] = $sku;
				}

				foreach ($pds as $pd){
					unset($sku_not_pd[$pd['sku']]);
					
					//if(is_null($pd['purchase_price']) || is_null($pd['additional_cost'])){
						$rtn['data']['need_set_price'][$pd['sku']]['purchase_price'] = $pd['purchase_price'];
						$rtn['data']['need_set_price'][$pd['sku']]['additional_cost'] = $pd['additional_cost'];
						$rtn['data']['need_set_price'][$pd['sku']]['img'] = $pd['photo_primary'];
						$rtn['data']['need_set_price'][$pd['sku']]['name'] = $pd['prod_name_ch'];
					//}
				}
				//将未建立商品的sku也放入需要设置价格的商品数组
				if(!empty($sku_not_pd)){
					foreach ($sku_not_pd as $np){
						$rtn['data']['need_set_price'][$np]['purchase_price'] = '';
						$rtn['data']['need_set_price'][$np]['additional_cost'] ='';
						
						
						$prodInfo = CdiscountOfferList::find()->where(['product_id'=>$order_itemids_info[$np]['source_itemid']])
								->andWhere(['not',['img'=>null]])->one();
						if($prodInfo<>null && !empty($prodInfo->img)){
							$photos = json_decode($prodInfo->img,true);
							$photo_primary = empty($photos[0])?'':$photos[0];
						}
						if(!empty($photo_primary)){
							$photo_primary = ImageCacherHelper::getImageCacheUrl($photo_primary,$uid,1);
						}
						else 
							$photo_primary = '/images/batchImagesUploader/no-img.png';
						$rtn['data']['need_set_price'][$np]['img'] = $photo_primary;
						$rtn['data']['need_set_price'][$np]['name'] = '';
						$rtn['data']['need_set_price'][$np]['unExisting'] = true;
					}
				}
			}
			//列出需要设置物流费的订单
			$orders = OdOrder::find()->where(['order_id'=>$id_arr,/*'logistics_cost'=>null*/])->asArray()->all();
			$currencies =[];
			foreach ($orders as $od){
				$rtn['data']['need_logistics_cost'][$od['order_source_order_id']] = [
					'order_id'=>$od['order_id'],
					'logistics_cost' => $od['logistics_cost'],
					'logistics_weight' => $od['logistics_weight'],
				];
				if(!in_array($od['currency'], $currencies))
					$currencies[] = $od['currency'];
			}
			
			//所有用到的货币
			$exchange_data = [];
			$exchange_loss = [];
			$exchange_config = ConfigHelper::getConfig("Order/CurrencyExchange",'NO_CACHE');
			if(empty($exchange_config))
				$exchange_config = [];
			else{
				$exchange_config = json_decode($exchange_config,true);
				if(empty($exchange_config))
					$exchange_config = [];
			}
			/*exchange_config:
			 * array(
			 * 		$using_currency_1=>[
			 * 				$target_currency_1=>$exchange_rate_1,
			 * 				$target_currency_2=>$exchange_rate_2,
			 * 				...
			 * 			],
			 * 		$using_currency_1=>[...],
			 * 		...
			 * )
			 */
			//汇损
			$exchange_loss_config = ConfigHelper::getConfig("Order/CurrencyExchangeLoss",'NO_CACHE');
			if(empty($exchange_loss_config))
				$exchange_loss_config = [];
			else{
				$exchange_loss_config = json_decode($exchange_loss_config,true);
				if(empty($exchange_loss_config))
					$exchange_loss_config = [];
			}
			
			foreach ($currencies as $currency){
				$currency = strtoupper($currency);
				//币种对应的RMB汇率
				$exchange_data[$currency] = '';//默认空值
				if(isset($exchange_config[$currency]['CNY'])){
					//采用用户设置过得人民币汇率
					$exchange_data[$currency] = $exchange_config[$currency]['CNY'];
				}
				else{
					//采用系统默认汇率
					if(isset(StandardConst::$EXCHANGE_RATE_OF_RMB[$currency]))
						$exchange_data[$currency] = StandardConst::$EXCHANGE_RATE_OF_RMB[$currency];
				}
				//币种对应汇损
				$exchange_loss[$currency] = '';//默认空值
				if(isset($exchange_loss_config[$currency])){
					$exchange_loss[$currency] = $exchange_loss_config[$currency];
				}
			}
			$rtn['data']['exchange'] = $exchange_data;
			$rtn['data']['exchange_loss'] = $exchange_loss;
		}else{//异常
			$rtn['success'] = false;
			$rtn['message'] = "没有指定需要处理的订单";
			return $rtn;
		}
		
		return $rtn;
	}
	
	
	/**
	 * 计算订单利润
	 * @param	$order_ids		array	
	 * @param	$price_type		int		0：pd_product.purchase_price; 1:od_order_item.purchase_price
	 * @return	array
	 * @author luzhiliang	2016-03-03
	 */
	public static function profitOrderByOrderId_old($order_ids,$price_type=0){
		$rtn['success'] = true;
		$rtn['message'] = "";
		$id_arr = [];
		foreach ($order_ids as $order_id){
			if(trim($order_id)!=='')
				$id_arr[] = trim($order_id);
		}
		
		$items = OdOrderItem::find()->select('sku')->distinct(true)->where(['order_id'=>$id_arr])->asArray()->all();
		$skus = [];
		foreach ($items as $item){
			$skus[] = $item['sku'];
		}
		
		//@todo
		//nonDeliverySku,暂时只有Cdiscount平台有。
		$nonDeliverySku = CdiscountOrderInterface::getNonDeliverySku();
		
		$productInfos = [];
		$existing_skus = [];
		if(empty($skus)){//异常
			$rtn['success'] = false;
			$rtn['message'] = "订单商品详情丢失";
			return $rtn;
		}else {
			$pds = Product::find()->where(['sku'=>$skus])->asArray()->all();
			foreach ($pds as $pd){
				$productInfos[$pd['sku']] = $pd;
				$existing_skus[] = $pd['sku'];
			}
		}
		$OrderItemLastPrice = [];
		
		//获取已设置的汇率
		$exchange_config = ConfigHelper::getConfig("Order/CurrencyExchange",'NO_CACHE');
		if(empty($exchange_config))
			$exchange_config = [];
		else{
			$exchange_config = json_decode($exchange_config,true);
			if(empty($exchange_config))
				$exchange_config = [];
		}
		//获取已设置的RMB汇损
		$exchange_loss_data = [];
		$exchange_loss_config = ConfigHelper::getConfig("Order/CurrencyExchangeLoss",'NO_CACHE');
		if(empty($exchange_loss_config))
			$exchange_loss_config = [];
		else{
			$exchange_loss_config = json_decode($exchange_loss_config,true);
			if(empty($exchange_loss_config))
				$exchange_loss_config = [];
		}
		
		//$transaction = \Yii::$app->get('subdb')->beginTransaction();
		$orders = OdOrder::find()->where(['order_id'=>$id_arr])->all();
		foreach ($orders as $od){
			$product_cost = 0;//商品成本
			$product_cost_str = '';//商品成本计算式
			$logistics_cost = empty($od->logistics_cost)?0:floatval($od->logistics_cost);//物流成本
			$currency = strtoupper($od->currency);//币种
			if(!empty($exchange_config[$currency]['CNY']))
				$EXCHANGE_RATE = $exchange_config[$currency]['CNY'];
			else
				$EXCHANGE_RATE = !empty(StandardConst::$EXCHANGE_RATE_OF_RMB[$currency])?StandardConst::$EXCHANGE_RATE_OF_RMB[$currency]:'--';//币种对应的RMB汇率
			if($EXCHANGE_RATE=='--'){
				$rtn['success'] = false;
				$rtn['message'] .= $od->order_source_order_id."利润计算失败:未设置币种".$currency."对应的人民币汇率;<br>";
				continue;
			}
			$EXCHANGE_LOSS = isset($exchange_loss_config[$currency])?floatval($exchange_loss_config[$currency]):0;
			
			$od_items = OdOrderItem::find()->where(['order_id'=>$od->order_id])->all();
			$all_item_ok = true;//所有item ok ，才计算利润
			foreach ($od_items as &$od_item){
				$quantity = floatval($od_item->quantity);
				//$additional_cost = empty($productInfos[$od_item->sku]['additional_cost'])?0:floatval($productInfos[$od_item->sku]['additional_cost']);
				//$product_cost += $additional_cost * $quantity;
				//$product_cost_str = empty($product_cost_str)?$additional_cost.'('.$od_item->sku.'额外成本)*'.$quantity : $product_cost_str.'+'.$additional_cost.'('.$od_item->sku.'额外成本)*'.$quantity;
				
				//nonDeliverySku 直接取用当次订单价格作为采购价
				if(in_array(strtoupper($od_item->sku),$nonDeliverySku)){
					continue;
					$purchase_price = floatval($od_item->price);
				}
				else{
					if(!$price_type){//使用product表的采购价
						//如果商品存在于商品模块，采用商品模块设定的采购价
						if(in_array($od_item->sku,$existing_skus)){
							if(is_null($productInfos[$od_item->sku]['purchase_price'])){
								//如果采购价为null，则表示未设置过，报提示
								$rtn['success'] = false;
								$rtn['message'] .= $od->order_source_order_id."利润计算失败:商品(sku".$od_item->sku.")未设置好采购价;<br>";
								$all_item_ok = false;
								break;
							}else{
								$purchase_price = floatval($productInfos[$od_item->sku]['purchase_price']);
							}
							
							$additional_cost = empty($productInfos[$od_item->sku]['additional_cost'])?0:floatval($productInfos[$od_item->sku]['additional_cost']);
							$product_cost += $additional_cost * $quantity;
							//$product_cost_str = empty($product_cost_str)?$additional_cost.'*'.$quantity : $product_cost_str.'+'.$additional_cost.'*'.$quantity;
							
						}else{//如果不存在于商品模块，则采用上次order item采购价
							if(isset($OrderItemLastPrice[$od_item->sku]))
								$purchase_price = $OrderItemLastPrice[$od_item->sku];
							else{
								$purchase_price = self::getOrderItemLastPrice($od_item->sku);
								if($purchase_price==false){//如果上次order item 采购价未出现，也报提示
									$rtn['success'] = false;
									$rtn['message'] .= $od->order_source_order_id."利润计算失败:商品(sku".$od_item->sku.")未设置好采购价;<br>";
									$all_item_ok = false;
									break;
								}else 
									$OrderItemLastPrice[$od_item->sku] = $purchase_price;
							}
						}
						//采购价和order item 采购价snapshot不同的时候，表示初始设置或者update snapshot
						if(floatval($od_item->purchase_price)!==$purchase_price){
							$od_item->purchase_price = $purchase_price;
							$od_item->purchase_price_form = null;
							$od_item->purchase_price_to = null;
						}
					}else{
						//使用order_item snapshot价格来计算
						if(empty($od_item->purchase_price)){
							$rtn['success'] = false;
							$rtn['message'] .= $od->order_source_order_id."利润计算失败:商品未设置好采购价;<br>";
							$all_item_ok = false;
							break;
						}
						$purchase_price = floatval($od_item->purchase_price);
					}
				}
				$product_cost += $purchase_price * $quantity;
				$itme_cost_str = '<br>&nbsp;&nbsp;&nbsp;&nbsp;'.$od_item->sku.'：(采购价'.$purchase_price.(isset($additional_cost)?"+额外$additional_cost)":')').'*'.$quantity;
				$product_cost_str = empty($product_cost_str)?$itme_cost_str : $product_cost_str.$itme_cost_str;
			}
			if(!$all_item_ok)
				continue;
			//@todo	//需要确认grand_total是否已经剔除平台佣金
					//Cdiscount是已经剔除佣金的。
			$profit = floatval($od->grand_total) * $EXCHANGE_RATE * (1-$EXCHANGE_LOSS/100) - $product_cost - $logistics_cost;
			$od->profit = $profit;
			$addi_info = $od->addi_info;
			$addi_info = json_decode($addi_info,true);
			if(empty($addi_info))
				$addi_info = [];
			
			$addi_info['exchange_rate'] = $EXCHANGE_RATE;
			$addi_info['exchange_loss'] = $EXCHANGE_LOSS;
			$addi_info['product_cost'] = $product_cost_str;
			$addi_info['logistics_cost'] = $logistics_cost;
			$od->addi_info = json_encode($addi_info);
			
			//保存利润信息
			if(!$od->save(false)){//失败
				$rtn['success'] = false;
				$rtn['message'] .= $od->order_source_order_id."利润计算失败;<br>";
				SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',print_r($od->getErrors(),true));
			}else{//成功则update order_item snapshot
				foreach ($od_items as $od_item){
					if(!$od_item->save(false)){
						SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',print_r($od_item->getErrors(),true));
					}
				}
			}
		}
		
		return $rtn;
	}
	
	/**
	 +----------------------------------------------------------
	 *  计算订单利润
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2016/09/20				初始化
	 +----------------------------------------------------------
	 **/
	public static function profitOrderByOrderId($order_ids,$price_type=0){
	    $rtn['success'] = true;
	    $rtn['message'] = "";
	    $id_arr = [];
	    foreach ($order_ids as $order_id){
	        if(trim($order_id)!=='')
	            $id_arr[] = trim($order_id);
	    }
	
	    $items = OdOrderItem::find()->select(['sku', 'root_sku'])->distinct(true)->where(['order_id'=>$id_arr])->andwhere("manual_status is null or manual_status!='disable'")->asArray()->all();
	    $skus = [];
	    foreach ($items as $item){
	        if(!empty($item['root_sku'])){
	            $skus[] = $item['root_sku'];
	        }
	    }
	
	    //@todo
	    //nonDeliverySku,暂时只有Cdiscount平台有。
	    $nonDeliverySku = CdiscountOrderInterface::getNonDeliverySku();
	
	    $productInfos = [];
	    $existing_skus = [];
	    if(!empty($skus)){
	        $pds = Product::find()->where(['sku'=>$skus])->asArray()->all();
	        foreach ($pds as $pd){
	            $productInfos[$pd['sku']] = $pd;
	            $existing_skus[] = $pd['sku'];
	        }
	    }
	     
	    $OrderItemLastPrice = [];
	
	    //获取已设置的汇率
	    $exchange_config = ConfigHelper::getConfig("Profit/CurrencyExchange",'NO_CACHE');
	    if(empty($exchange_config))
	        $exchange_config = [];
	    else{
	        $exchange_config = json_decode($exchange_config,true);
	        if(empty($exchange_config))
	            $exchange_config = [];
	    }
	    //获取已设置的RMB汇损
	    $exchange_loss_data = [];
	    $exchange_loss_config = ConfigHelper::getConfig("Order/CurrencyExchangeLoss",'NO_CACHE');
	    if(empty($exchange_loss_config))
	        $exchange_loss_config = [];
	    else{
	        $exchange_loss_config = json_decode($exchange_loss_config,true);
	        if(empty($exchange_loss_config))
	            $exchange_loss_config = [];
	    }
	
	    //$transaction = \Yii::$app->get('subdb')->beginTransaction();
	    $exist_info = [];
	    $ProfitAdds = [];
	    $orders = OdOrder::find()->where(['order_id'=>$id_arr])->all();
	    foreach ($orders as $od){
	        $product_cost = 0;//商品成本
	        $product_cost_str = '';//商品成本计算式
	        $commission = 0;   //佣金
	        $logistics_cost = empty($od->logistics_cost)?0:floatval($od->logistics_cost);//物流成本
	        $currency = strtoupper($od->currency);//币种
	        if(!empty($exchange_config[$currency])){
	            $EXCHANGE_RATE = $exchange_config[$currency];
	        }
	        else{
	            if($currency == 'PH'){
	                $currency = 'PHP';
	            }
	            //获取最新汇率
	            $EXCHANGE_RATE = \eagle\modules\statistics\helpers\ProfitHelper::GetExchangeRate($currency);
	            if(empty($EXCHANGE_RATE)){
	                $EXCHANGE_RATE = '--';
	            }
	            //$EXCHANGE_RATE = !empty(StandardConst::$EXCHANGE_RATE_OF_RMB[$currency])?StandardConst::$EXCHANGE_RATE_OF_RMB[$currency]:'--';//币种对应的RMB汇率
	        }
	        if($EXCHANGE_RATE=='--'){
	            $rtn['success'] = false;
	            $rtn['message'] .= $od->order_source_order_id."利润计算失败:未设置币种".$currency."对应的人民币汇率;<br>";
	            continue;
	        }
	        $EXCHANGE_LOSS = isset($exchange_loss_config[$currency])?floatval($exchange_loss_config[$currency]):0;
	         
	        $od_items = OdOrderItem::find()->where(['order_id'=>$od->order_id])->andwhere("manual_status is null or manual_status!='disable'")->all();
	        foreach ($od_items as &$od_item){
	            if(!empty($od_item->root_sku)){
	                $sku = $od_item->root_sku;
	            }
	            else{
	                //$sku = $od_item->sku;
	                $sku = '';
	            }
	             
	            $quantity = floatval($od_item->quantity);
	            //$additional_cost = empty($productInfos[$od_item->sku]['additional_cost'])?0:floatval($productInfos[$od_item->sku]['additional_cost']);
	            //$product_cost += $additional_cost * $quantity;
	            //$product_cost_str = empty($product_cost_str)?$additional_cost.'('.$od_item->sku.'额外成本)*'.$quantity : $product_cost_str.'+'.$additional_cost.'('.$od_item->sku.'额外成本)*'.$quantity;
	
	            //nonDeliverySku 直接取用当次订单价格作为采购价
	            if(in_array(strtoupper($od_item->sku),$nonDeliverySku)){// dzt20190610 $sku改为$od_item->sku 判断
	                // $purchase_price = floatval($od_item->price);
	                continue;
	            }
	            else{
	                if(!$price_type){//使用product表的采购价
	                    //如果商品存在于商品模块，采用商品模块设定的采购价
	                    if(in_array($sku,$existing_skus)){
	                        if(is_null($productInfos[$sku]['purchase_price'])){
	                            $purchase_price = 0;
	                        }else{
	                            $purchase_price = floatval($productInfos[$sku]['purchase_price']);
	                        }
	
	                        //当是捆绑商品时，从子产品计算出采购价、其它成本
	                        if($productInfos[$sku]['type'] == 'B'){
	                            //查询对应的捆绑商品信息
	                            $bundle = ProductBundleRelationship::find()->select(['assku', 'qty'])->where(['bdsku' => $sku])->asArray()->all();
	                            if(!empty($bundle)){
	                                $asskus = [];
	                                $assku_arr = [];
	                                foreach ($bundle as $val){
	                                    $asskus[] = $val['assku'];
	                                    $assku_arr[$val['assku']] = $val['qty'];
	                                }
	                                //查询子商品对应的采购价、其它成本
	                                $assku_pro_arr = [];
	                                $assku_pros = Product::find()->select(['sku', 'purchase_price', 'additional_cost'])->where(['sku' => $asskus])->asArray()->all();
	                                foreach ($assku_pros as $val){
	                                    $assku_pro_arr[$val['sku']]['purchase_price'] = $val['purchase_price'];
	                                    $assku_pro_arr[$val['sku']]['additional_cost'] = $val['additional_cost'];
	                                }
	                                //重新计算捆绑商品采购价、其它成本
	                                $purchase_price2 = 0;
	                                $additional_cost2 = 0;
	                                foreach ($assku_arr as $assku => $qty){
	                                    if(!empty($qty)){
	                                        if(!empty($assku_pro_arr[$assku])){
	                                            if(!empty($assku_pro_arr[$assku]['purchase_price'])){
	                                                $purchase_price2 += $assku_pro_arr[$assku]['purchase_price'] * $qty;
	                                            }
	                                            if(!empty($assku_pro_arr[$assku]['additional_cost'])){
	                                                $additional_cost2 += $assku_pro_arr[$assku]['additional_cost'] * $qty;
	                                            }
	                                        }
	                                    }
	
	                                    if(!empty($purchase_price2))
	                                        $purchase_price = $purchase_price2;
	                                    if(!empty($additional_cost2))
	                                        $productInfos[$sku]['additional_cost'] = $additional_cost2;
	
	                                }
	                            }
	                        }
	                        	
	                        $additional_cost = empty($productInfos[$sku]['additional_cost']) ? 0 : floatval($productInfos[$sku]['additional_cost']);
	                        $product_cost += $additional_cost * $quantity;
	                        //$product_cost_str = empty($product_cost_str)?$additional_cost.'*'.$quantity : $product_cost_str.'+'.$additional_cost.'*'.$quantity;
	                        	
	                    }else{//如果不存在于商品模块，则采用上次order item采购价
	                        if(isset($OrderItemLastPrice[$sku]))
	                            $purchase_price = $OrderItemLastPrice[$sku];
	                        else{
	                            $purchase_price = self::getOrderItemLastPrice($sku);
	                            if($purchase_price==false){
	                                if(!empty($od_item->purchase_price)){
	                                    $OrderItemLastPrice[$sku] = $od_item->purchase_price;
	                                    $purchase_price = $od_item->purchase_price;
	                                }
	                                else{
	                                    $OrderItemLastPrice[$sku] = 0;
	                                    $purchase_price = 0;
	                                }
	                            }else{
	                                $OrderItemLastPrice[$sku] = $purchase_price;
	                            }
	                        }
	                    }
	                    //采购价和order item 采购价snapshot不同的时候，表示初始设置或者update snapshot
	                    if(floatval($od_item->purchase_price)!==$purchase_price && !empty($purchase_price)){
	                        $od_item->purchase_price = $purchase_price;
	                        $od_item->purchase_price_form = null;
	                        $od_item->purchase_price_to = null;
	                    }
	                }else{
	                    //使用order_item snapshot价格来计算
	                    if(empty($od_item->purchase_price)){
	                        $purchase_price = 0;
	                    }
	                    else{
	                        $purchase_price = floatval($od_item->purchase_price);
	                    }
	                }
	            }
	             
	            $product_cost += $purchase_price * $quantity;
	            $itme_cost_str = '<br>&nbsp;&nbsp;&nbsp;&nbsp;'.$od_item->sku.'：(采购价'.$purchase_price.(isset($additional_cost)?"+额外$additional_cost)":')').'*'.$quantity;
	            $product_cost_str = empty($product_cost_str)?$itme_cost_str : $product_cost_str.$itme_cost_str;
	             
	            //计算产品佣金
	            if(!empty($productInfos[$sku]) && !empty($productInfos[$sku]['addi_info'])){
	                $addi_info = json_decode($productInfos[$sku]['addi_info'], true);
	                if(!empty($addi_info['commission_per'][$od->order_source])){
	                    $per = $addi_info['commission_per'][$od->order_source];
	                    if(!empty($per) && !empty($od_item->quantity) && !empty($od_item->price)){
	                        $commission += $od_item->quantity * $od_item->price * $per / 100;
	                    }
	                }
	            }
	        }
	
	        //@todo	//需要确认grand_total是否已经剔除平台佣金
	        //Cdiscount是已经剔除佣金的。
	        //总价 RMB，产品总价 + 运费 - 折扣------cd 的折扣存在的是佣金内容，则不需扣减
	        //当是手工订单时，用合计金额
	        if($od->order_capture == 'Y' || $od->order_source == 'aliexpress')
	            $grand_total = floatval($od->grand_total) * $EXCHANGE_RATE * (1-$EXCHANGE_LOSS/100);
	        else if(in_array($od->order_source, ['cdiscount', 'lazada', 'linio']))
	            $grand_total = floatval($od->subtotal + $od->shipping_cost) * $EXCHANGE_RATE * (1-$EXCHANGE_LOSS/100);
	        else
	            $grand_total = floatval($od->subtotal + $od->shipping_cost - $od->discount_amount) * $EXCHANGE_RATE * (1-$EXCHANGE_LOSS/100);
	
	        //佣金 RMB，ebay、wish、cd，用平台拉取的佣金计算，其它用本地计算佣金计算
	        if(in_array($od->order_source, ['cdiscount', 'wish', 'ebay'])){
	            $commission_total = floatval($od->commission_total) * $EXCHANGE_RATE * (1-$EXCHANGE_LOSS/100);
	        }
	        else{
	            $commission_total = floatval($commission) * $EXCHANGE_RATE * (1-$EXCHANGE_LOSS/100);
	        }
	        $commission_total = empty($commission_total) ? 0 : round($commission_total, 2);
	
	        //paypal手续费 RMB
	        $paypal_fee = floatval($od->paypal_fee) * $EXCHANGE_RATE * (1-$EXCHANGE_LOSS/100);
	        //实收费用，总价 RMB - 佣金 - paypal手续费
	        $actual_charge = $grand_total - $commission_total - $paypal_fee;
	        //利润
	        $profit = $actual_charge - $product_cost - $logistics_cost;
	
	        $dis_logistics_cost = $profit - $od->profit;
	        $od->profit = $profit;
	        $addi_info = $od->addi_info;
	        $addi_info = json_decode($addi_info,true);
	        if(empty($addi_info))
	            $addi_info = [];
	         
	        $addi_info['exchange_rate'] = $EXCHANGE_RATE;
	        $addi_info['exchange_loss'] = $EXCHANGE_LOSS;
	        $addi_info['product_cost'] = $product_cost_str;
	        $addi_info['purchase_cost'] = $product_cost;
	        $addi_info['grand_total'] = $grand_total;
	        $addi_info['logistics_cost'] = $logistics_cost;
	        $addi_info['commission_total'] = $commission_total;
	        $addi_info['paypal_fee'] = $paypal_fee;
	        $addi_info['actual_charge'] = $actual_charge;
	        $od->addi_info = json_encode($addi_info);
	         
	        //保存利润信息
	        if(!$od->save(false)){//失败
	            $rtn['success'] = false;
	            $rtn['message'] .= $od->order_source_order_id."利润计算失败;<br>";
	            SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',print_r($od->getErrors(),true));
	        }else{//成功则update order_item snapshot
	            foreach ($od_items as $od_item){
	                if(!$od_item->save(false)){
	                    SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',print_r($od_item->getErrors(),true));
	                }
	            }
	             
	            //整理需要更新的数据
	            $order_date = date("Y-m-d",$od->order_source_create_time);
	            $platform = $od->order_source;
	            $order_type = $od->order_type;
	            $seller_id = $od->selleruserid;
	            $order_status = $od->order_status;
	            $info = $order_date.'&'.$platform.'&'.$seller_id.'&'.$order_status;
	            if(!in_array($info, $exist_info))
	            {
	                $ProfitAdds[$info] = [
	                        'order_date' => $order_date,
	                        'platform' => $platform,
	                        'order_type'=>$order_type,
	                        'seller_id' => $seller_id,
	                        'profit_cny' => $dis_logistics_cost,
	                        'order_status' => $order_status,
	                ];
	                $exist_info[] = $info;
	            }
	            else
	                $ProfitAdds[$info]['profit_cny'] = $ProfitAdds[$info]['profit_cny'] + $dis_logistics_cost;
	        }
	    }
	     
	    //更新dash_board信息
	    foreach ($ProfitAdds as $key => $ProfitAdd)
	    {
	        DashBoardStatisticHelper::SalesProfitAdd($ProfitAdd['order_date'], $ProfitAdd['platform'], $ProfitAdd['order_type'], $ProfitAdd['seller_id'], $ProfitAdd['profit_cny'], false, $ProfitAdd['order_status']);
	    }
	
	    return $rtn;
	}
	
	
	public static function getOrderItemLastPrice($sku){
		$lastItem = OdOrderItem::find()->where(['sku'=>$sku])->orderBy(" purchase_price_to DESC, order_item_id DESC ")->limit(1)->offset(0)->One();
		if(!empty($lastItem)){
			$lastPrice = $lastItem->purchase_price;
			if(is_null($lastPrice)){
				return false;
			}else 
				return $lastPrice;
		}else 
			return $lastPrice = 0.00;
	}
	
	/**
	 * +----------------------------------------------------------
	 * 手动设置订单成本：商品采购成本，商品额外成本，订单发货成
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param	array
	 *+----------------------------------------------------------
	 * @return 	操作结果Array
	 * +----------------------------------------------------------
	 * log			name		date			note
	 * @author 		lzhl		2016/03/16		初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function setOrderCost_old($data){
		$rtn['success'] = true;
		$rtn['message'] = '';
		$order_ids = explode(',', $data['order_ids']);
		
		$journal_id = SysLogHelper::InvokeJrn_Create("Order",__CLASS__, __FUNCTION__ , array($order_ids,$data));
		$errMsg = '';
		
		//设置汇率	//默认兑换成RMB
		//获取已设置的汇率
		$exchange_config = ConfigHelper::getConfig("Order/CurrencyExchange",'NO_CACHE');
		if(empty($exchange_config))
			$exchange_config = [];
		else{
			$exchange_config = json_decode($exchange_config,true);
			if(empty($exchange_config))
				$exchange_config = [];
		}
		if(!empty($data['exchange']) && is_array($data['exchange'])){
			foreach ($data['exchange'] as $currency=>$rate){
				$currency = strtoupper($currency);
				$exchange_config[$currency]['CNY'] = floatval($rate);
			}
		}
		if(!empty($exchange_config))
			ConfigHelper::setConfig("Order/CurrencyExchange", json_encode($exchange_config));
		
		//设置汇损
		//获取已设置的汇率
		$exchange_loss_config = ConfigHelper::getConfig("Order/CurrencyExchangeLoss",'NO_CACHE');
		if(empty($exchange_loss_config))
			$exchange_loss_config = [];
		else{
			$exchange_loss_config = json_decode($exchange_loss_config,true);
			if(empty($exchange_loss_config))
				$exchange_loss_config = [];
		}
		if(!empty($data['exchange_loss']) && is_array($data['exchange_loss'])){
			foreach ($data['exchange_loss'] as $currency=>$exchange_loss){
				$currency = strtoupper($currency);
				$exchange_loss_config[$currency] = floatval($exchange_loss);
			}
		}
		if(!empty($exchange_loss_config))
			ConfigHelper::setConfig("Order/CurrencyExchangeLoss", json_encode($exchange_loss_config));
		
		//设置商品成本 start
		//$transaction = \Yii::$app->get('subdb')->beginTransaction();
		if(empty($data['price_type'])){//采购价录入方式为更改商品模块采购价
			foreach ($data['sku'] as $index=>$sku){
				$pd = Product::findOne($sku);
				//if(!in_array($sku,['CM8615041005A','GP6216022302A','JPAM14102301A-1','TR1000000019388','UP8515082002B','INTERETBCA','TR1000000019215','B004S493VG','B12000000011099'])){
				//	print_r($pd);exit();
				//}
				if(!empty($pd)){
					//商品存在
					$pd->purchase_price = $data['purchase_price'][$index];
					$pd->additional_cost = $data['additional_cost'][$index];
					if(!$pd->save()){
						$errMsg .= print_r($pd->getErrors(),true);
						SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',print_r($pd->getErrors(),true));
						$rtn['success'] = false;
						$rtn['message'] .= '商品'.$sku.'采购价修改失败;';
						continue;
					}
					//修改首选供应商采购价
					$pdSupplier = ProductSuppliers::find()->where(['sku'=>$sku])->orderBy("priority ASC")->limit(1)->offset(0)->One();
					if(!empty($pdSupplier)){
						//如果商品已经设置过供应商信息，则更新供应商采购价
						$pdSupplier->purchase_price = $data['purchase_price'][$index];
						if(!$pdSupplier->save()){
							$errMsg .= print_r($pdSupplier->getErrors(),true);
							SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',print_r($pdSupplier->getErrors(),true));
							$rtn['success'] = false;
							$rtn['message'] .= '商品'.$sku.'首选供应商采购价修改失败;';
							continue;
						}
					}
				}else{
					//商品未创建，转到order item
					$updateItems = OdOrderItem::updateAll(
						['purchase_price'=>floatval($data['purchase_price'][$index])+floatval($data['additional_cost'][$index]) ],
						['order_id'=>$order_ids,'sku'=>$sku]
						);
					/*
					if(!$updateItems){
						SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error','update order item falier,order ids:'.print_r($order_ids,true).",sku:$sku");
						$rtn['success'] = false;
						$rtn['message'] .= '商品'.$sku.'订单商品采购价修改失败;';
						continue;
					}
					*/	
				}
			}
		}else{//录入方式为只记录order item snapshot
			foreach ($data['sku'] as $index=>$sku){
				//商品未创建，转到order item
				$updateItems = OdOrderItem::updateAll(
						['purchase_price'=>floatval($data['price_based_on_order'][$index])],
						['order_id'=>$order_ids,'sku'=>$sku]
				);
				//var_dump($updateItems);
				/*
				if(!$updateItems){
					SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error','update order item falier,order ids:'.print_r($order_ids,true).",sku:$sku");
					$rtn['success'] = false;
					$rtn['message'] .= '商品'.$sku.'订单商品采购价修改失败;';
					continue;
				}
				*/
			}
		}
		//设置商品成本 end
		
		//设置运费成本 start
		$need_set_logistics_cost_order = [];
		if(!empty($data['logistics_cost'])){
			foreach ($data['logistics_cost'] as $order_no=>$logistics_cost){
				if(!in_array($order_no, $need_set_logistics_cost_order))
					$need_set_logistics_cost_order[] = $order_no;
			}
		}
		if(!empty($data['logistics_weight'])){
			foreach ($data['logistics_weight'] as $order_no=>$logistics_weight){
				if(!in_array($order_no, $need_set_logistics_cost_order))
					$need_set_logistics_cost_order[] = $order_no;
			}
		}	
		foreach ($need_set_logistics_cost_order as $order_no){
			$order = OdOrder::find()->where(['order_source_order_id'=>$order_no])->One();
			if(!empty($order)){
				$order->logistics_cost = empty($data['logistics_cost'][$order_no])?0 : $data['logistics_cost'][$order_no];
				$order->logistics_weight = empty($data['logistics_weight'][$order_no])?0 : $data['logistics_weight'][$order_no];
				if(!$order->save()){
					$errMsg .= print_r($order->getErrors(),true);
					SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',print_r($order->getErrors(),true));
					$rtn['success'] = false;
					$rtn['message'] .= '订单'.$order_no.'物流费用修改失败;';
					continue;
				}
			}
		}
		
		//设定运费成本 end
		$log = $rtn;
		$log['errMsg'] = $errMsg;
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $log);
		return $rtn;
	}
	
	/**
	 * +----------------------------------------------------------
	 * 手动设置订单成本：商品采购成本，商品额外成本，订单发货成
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param	array
	 *+----------------------------------------------------------
	 * @return 	操作结果Array
	 * +----------------------------------------------------------
	 * log			name		date			note
	 * @author 		lzhl		2016/03/16		初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function setOrderCost($data, $journal_id){
	    $rtn['success'] = true;
	    $rtn['message'] = '';
	    $order_ids = explode(',', $data['order_ids']);
	    $errMsg = '';
	
	    //设置汇率	//默认兑换成RMB
	    //获取已设置的汇率
	    $exchange_config = ConfigHelper::getConfig("Profit/CurrencyExchange",'NO_CACHE');
	    if(empty($exchange_config))
	        $exchange_config = [];
	    else{
	        $exchange_config = json_decode($exchange_config,true);
	        if(empty($exchange_config))
	            $exchange_config = [];
	    }
	    if(!empty($data['exchange']) && is_array($data['exchange'])){
	        foreach ($data['exchange'] as $currency=>$rate){
	            $currency = strtoupper($currency);
	             
	            //当汇率变更时
	            if(!empty($exchange_config[$currency]) && floatval($rate) != floatval($exchange_config[$currency])){
	                $exchange_config[$currency] = floatval($rate);
	            }
	            else if(empty($exchange_config[$currency])){
	                //获取雅虎最新汇率
	                $new_rate = \eagle\modules\statistics\helpers\ProfitHelper::GetExchangeRate($currency);
	                if(empty($new_rate) || floatval($new_rate) != floatval($rate)){
	                    $exchange_config[$currency] = floatval($rate);
	                }
	            }
	        }
	    }
	    if(!empty($exchange_config))
	        ConfigHelper::setConfig("Profit/CurrencyExchange", json_encode($exchange_config));
	
	    //设置汇损
	    //获取已设置的汇损
	    $exchange_loss_config = ConfigHelper::getConfig("Order/CurrencyExchangeLoss",'NO_CACHE');
	    if(empty($exchange_loss_config))
	        $exchange_loss_config = [];
	    else{
	        $exchange_loss_config = json_decode($exchange_loss_config,true);
	        if(empty($exchange_loss_config))
	            $exchange_loss_config = [];
	    }
	    if(!empty($data['exchange_loss']) && is_array($data['exchange_loss'])){
	        foreach ($data['exchange_loss'] as $currency=>$exchange_loss){
	            $currency = strtoupper($currency);
	            $exchange_loss_config[$currency] = floatval($exchange_loss);
	        }
	    }
	    if(!empty($exchange_loss_config))
	        ConfigHelper::setConfig("Order/CurrencyExchangeLoss", json_encode($exchange_loss_config));
	
	    //设置商品成本 start
	    //$transaction = \Yii::$app->get('subdb')->beginTransaction();
	    if(empty($data['price_type'])){//采购价录入方式为更改商品模块采购价
	        foreach ($data['sku'] as $index=>$sku){
	            $pd = Product::findOne($sku);
	             
	            if(!empty($pd)){
	                //商品存在
	                $pd->purchase_price = $data['purchase_price'][$index];
	                $pd->additional_cost = $data['additional_cost'][$index];
	                if(!$pd->save()){
	                    $errMsg .= print_r($pd->getErrors(),true);
	                    SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',print_r($pd->getErrors(),true));
	                    $rtn['success'] = false;
	                    $rtn['message'] .= '商品'.$sku.'采购价修改失败;';
	                    continue;
	                }
	                //修改首选供应商采购价
	                $pdSupplier = ProductSuppliers::find()->where(['sku'=>$sku])->orderBy("priority ASC")->limit(1)->offset(0)->One();
	                if(!empty($pdSupplier)){
	                    //如果商品已经设置过供应商信息，则更新供应商采购价
	                    $pdSupplier->purchase_price = $data['purchase_price'][$index];
	                    if(!$pdSupplier->save()){
	                        $errMsg .= print_r($pdSupplier->getErrors(),true);
	                        SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',print_r($pdSupplier->getErrors(),true));
	                        $rtn['success'] = false;
	                        $rtn['message'] .= '商品'.$sku.'首选供应商采购价修改失败;';
	                        continue;
	                    }
	                }
	            }
	             
	            //更新order item采购价
	            $updateItems = OdOrderItem::updateAll(
	                    ['purchase_price'=>floatval($data['purchase_price'][$index])+floatval($data['additional_cost'][$index]) ],
	                    ['order_id'=>$order_ids,'sku'=>$sku]
	            );
	        }
	    }else{//录入方式为只记录order item snapshot
	        foreach ($data['sku'] as $index=>$sku){
	            //商品未创建，转到order item
	            $updateItems = OdOrderItem::updateAll(
	                    ['purchase_price'=>floatval($data['price_based_on_order'][$index])],
	                    ['order_id'=>$order_ids,'sku'=>$sku]
	            );
	            $updateItems = OdOrderItem::updateAll(
	                    ['purchase_price'=>floatval($data['price_based_on_order'][$index])],
	                    ['order_id'=>$order_ids,'root_sku'=>$sku]
	            );
	            //var_dump($updateItems);
	            /*
	             if(!$updateItems){
	             SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error','update order item falier,order ids:'.print_r($order_ids,true).",sku:$sku");
	             $rtn['success'] = false;
	             $rtn['message'] .= '商品'.$sku.'订单商品采购价修改失败;';
	             continue;
	             }
	            */
	        }
	    }
	    //设置商品成本 end
	
	    //设置运费成本 start
	    $need_set_logistics_cost_order = [];
	    if(!empty($data['logistics_cost'])){
	        foreach ($data['logistics_cost'] as $order_id=>$logistics_cost){
	            if(!in_array($order_id, $need_set_logistics_cost_order))
	                $need_set_logistics_cost_order[] = $order_id;
	        }
	    }
	    if(!empty($data['logistics_weight'])){
	        foreach ($data['logistics_weight'] as $order_id=>$logistics_weight){
	            if(!in_array($order_id, $need_set_logistics_cost_order))
	                $need_set_logistics_cost_order[] = $order_id;
	        }
	    }
	    foreach ($need_set_logistics_cost_order as $order_id){
	        $order = OdOrder::find()->where(['order_id'=>$order_id])->One();
	        if(!empty($order)){
	            $order->logistics_cost = empty($data['logistics_cost'][$order_id])?0 : $data['logistics_cost'][$order_id];
	            $order->logistics_weight = empty($data['logistics_weight'][$order_id])?0 : $data['logistics_weight'][$order_id];
	            if(!$order->save()){
	                $errMsg .= print_r($order->getErrors(),true);
	                SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',print_r($order->getErrors(),true));
	                $rtn['success'] = false;
	                $rtn['message'] .= '订单'.$order_id.'物流费用修改失败;';
	                continue;
	            }
	        }
	    }
	
	    //设定运费成本 end
	    $log = $rtn;
	    $log['errMsg'] = $errMsg;
	    SysLogHelper::InvokeJrn_UpdateResult($journal_id, $log);
	    return $rtn;
	}
	
	/**
	 * +----------------------------------------------------------
	 * 导入订单的发货物流成本信息
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param	array
	 *+----------------------------------------------------------
	 * @return 	操作结果Array
	 * +----------------------------------------------------------
	 * log			name		date			note
	 * @author 		lzhl		2016/03/16		初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function importOrderLogisticsCostData($logisticsData){
		$rtn['success'] = true;
		$rtn['message'] = '';
		$errMsg = '';
		
		$journal_id = SysLogHelper::InvokeJrn_Create("Order", __CLASS__, __FUNCTION__ ,array($logisticsData));
		
		if(!is_array($logisticsData)){
			$rtn['success'] = false;
			$rtn['message'] ='数据格式有误。';
			return $rtn;
		}
		
		foreach ($logisticsData as $index=>$info){
			$order_no = trim($info['order_number']);
			$track_no = trim($info['track_no']);
			if(empty($order_no) && empty($track_no)){
				$rtn['success'] = false;
				$rtn['message'] .= '第'.$index.'行订单号或物流号为空，跳过该行处理;<br>';
				continue;
			}
			$order_source = trim($info['order_source']);
			$order_source = strtolower($order_source);
			if(!empty(OdOrder::$orderSource))
				$platforms = array_keys(OdOrder::$orderSource);
			else 
				$platforms = ['ebay','amazon','aliexpress','wish','dhgate','cdiscount','lazada','linio','jumia','priceminister','bonanza','rumall'];
			if(!in_array($order_source,$platforms)){
				$rtn['success'] = false;
				$rtn['message'] .= '第'.$index.'行订单填入了错误的销售平台值，跳过该行处理;<br>';
				continue;
			}
			$logistics_cost = floatval($info['logistics_cost']);
			$logistics_weight = floatval($info['logistics_weight']);
			if(!empty($order_no))
				$order = OdOrder::find()->where(['order_source_order_id'=>$order_no,'order_source'=>$order_source])->One();
			else{
				$order_shipped = OdOrderShipped::find()->select("order_id")->distinct(true)->where(['tracking_number'=>$track_no,'order_source'=>$order_source])->asArray()->all();
				if(empty($order_shipped))
					$order=[];
				else{
					if(count($order_shipped)>1){
						$rtn['success'] = false;
						$rtn['message'] .= '第'.$index.'行订单填入的物流号对应了多张订单，不能设置指定订单的物流成本，跳过该行处理;<br>';
						continue;
					}else{
						$order_no = $order_shipped[0]['order_id'];
						$order = OdOrder::find()->where(['order_id'=>$order_no,'order_source'=>$order_source])->One();
					}
				}
			}
			
			if(!empty($order)){
				$order->logistics_cost = $logistics_cost;
				$order->logistics_weight = $logistics_weight;
				
				$transaction = \Yii::$app->get('subdb')->beginTransaction();
				if(!$order->save(false)){
					$errMsg .= print_r($order->getErrors(),true);
					$transaction->rollBack();
					SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',print_r($order->getErrors(),true));
					$rtn['success'] = false;
					$rtn['message'] .= '订单'.$order_no.'物流成本修改失败;<br>';
					continue;
				}
				$transaction->commit();
			}else{
				$rtn['success'] = false;
				$rtn['message'] .= '订单'.$order_no.'不存在，跳过该修改;<br>';
			}
		}
		
		$rtn['errMsg'] = $errMsg;
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
		
		return $rtn;
	}
	
	
}//end of class
?>