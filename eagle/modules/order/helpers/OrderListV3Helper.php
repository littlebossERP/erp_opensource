<?php
namespace eagle\modules\order\helpers;

use eagle\modules\order\models\OdOrder;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\order\helpers\CdiscountOrderHelper;
use eagle\modules\tracking\helpers\TrackingApiHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\catalog\models\Product;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\util\helpers\StandardConst;
use yii\helpers\Url;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\models\QueueDhgateGetorder;
use eagle\modules\carrier\helpers\CarrierDeclaredHelper;
use OSS\Result\Result;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\statistics\helpers\ProfitHelper;
use eagle\modules\catalog\helpers\ProductSuppliersHelper;

class OrderListV3Helper{
	public static $OrderCommonJSV3 = '1.241';
	
	//根据传进来的order_models进行解释
	public static function getOrderListInfoByOrderModels($order_models, $warehouses, $non17Track, $current_order_status, $skuListInfo, $current_exception_status = '', $special_selleruserids = '',$is_vip=false){
		//是否合并订单展示模式
		$isMergeOrder = 0;
		if (($current_order_status == 200) && ($current_exception_status == 223)){
			$isMergeOrder = 1;
		}
		
		//是否显示特殊的操作
		$tmp_is_show = true;
		if($current_order_status == ''){
			$tmp_is_show = \eagle\modules\order\helpers\OrderListV3Helper::getIsShowMenuAllOtherOperation();
		}
		
		//是否不显示可用库存
		$is_show_AvailableStock = \eagle\modules\order\helpers\OrderListV3Helper::getIsShowAvailableStock();
		
		$user=\Yii::$app->user->identity;
		$puid = $user->getParentUid();
		
		$orderInfos = array();
		
		//listing 站点前缀
		$siteEbayUrl = \common\helpers\Helper_Siteinfo::getSiteViewUrl();
		$siteEbayList = \common\helpers\Helper_Siteinfo::getSite();
		
		//获取国家列表
		$countryList = \eagle\modules\util\helpers\CountryHelper::getScopeCountry();
		
		//获取仓库列表
// 		$warehouses +=['-1'=>'未分配'];
		if(!isset($warehouses[-1])){
			$warehouses[-1] = '未分配';
		}
		
		//获取不同平台的卖家账号
		$selleruserids = \eagle\modules\platform\apihelpers\PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap(false, true, true);
		
		if(isset($special_selleruserids['lazada'])){
			unset($selleruserids['lazada']);
			$selleruserids = $selleruserids+$special_selleruserids;
		}
// 		print_r($selleruserids);
// 		exit;
		
		//检查报关信息是否存在
// 		$existProductResult = \eagle\modules\order\helpers\OrderBackgroundHelper::getExistProductRuslt($order_models);

		$stock_list = array();
		$pd_sp_list = array();
		
		//批量订单获取相关报关信息
		$order_items_info = array();
		$order_rootsku_product_image = array();
		if(count($order_models) > 0){
			$sku_list = array();
			$warehouse_list = ['0'];
			foreach ($order_models as $order){
				if(!empty($order->default_warehouse_id) && !in_array($order->default_warehouse_id, $warehouse_list)){
					$warehouse_list[] = $order->default_warehouse_id;
				}
				foreach($order->items as $item){
					$tmp_platform_itme_id = \eagle\modules\order\helpers\OrderGetDataHelper::getOrderItemSouceItemID($order->order_source , $item);
					$order_items_info[] = array('platform_type'=>$order->order_source, 'order_status'=>$order->order_status, 'xlb_item'=>$item->order_item_id,'sku'=>$item->sku, 'root_sku'=>$item->root_sku, 'itemID'=>$tmp_platform_itme_id, 'declaration'=>json_decode($item->declaration,true));
				
					if(!empty($item->root_sku) && !in_array($item->root_sku, $sku_list)){
						$sku_list[] = $item->root_sku;
					}
				}
			}
			
			//当设置个别平台使用商品库图片，获取对应订单的商品库图片
			$order_rootsku_product_image = OrderHelper::GetRootSkuImage($order_models);
			
			if($is_show_AvailableStock == false){
				$stock_list = \eagle\modules\inventory\apihelpers\InventoryApiHelper::GetSkuStock($sku_list, $warehouse_list);
			}
			
			//获取采购链接信息
			$pd_sp_list = ProductSuppliersHelper::getProductPurchaseLink($sku_list);
		}
		
		//统一获取报关信息
		$result_item_declared_info = CarrierDeclaredHelper::getOrderDeclaredInfoBatch($order_items_info, true);
// 		print_r($result_item_declared_info);
		
		
		if(count($order_models) > 0){
			foreach ($order_models as $order){
				//确定是否需要显示发货相关的信息 ,amazon有FBA
				$is_no_delivery = false;
				
				$addi_info_arr = empty($order->addi_info) ? array() : json_decode($order->addi_info,true);
				
				$tmp_platform_order_no = '';
				if(($order->order_source == 'ebay') && (!empty($order->order_source_srn))){
					$tmp_platform_order_no = $order->order_source_srn;
				}else{
					$tmp_platform_order_no = $order->order_source_order_id;
				}
				
				//获取旗子html
				$TagStr = OrderFrontHelper::generateTagIconHtmlByOrderId($order);
				
				if (!empty($TagStr)){
					$TagStr = "<span class='btn_tag_qtip".(stripos($TagStr,'egicon-flag-gray')?" div_space_toggle":"")."' data-order-id='".($order->order_id)."' >$TagStr</span>";
				}
				
				//获取金额 显示的HTML
				$amountInfoHtml = self::getDisplayOrderAmountInfoHTML($order);
				
				//店铺名
				$order_shop_name = $order->selleruserid;
				
				if(in_array($order->order_source, array('amazon', 'cdiscount', 'customized','aliexpress','ebay','shopee'))){
					if(isset($selleruserids[$order->order_source][$order->selleruserid])){
						$order_shop_name = $selleruserids[$order->order_source][$order->selleruserid];
					}
				}
				
				if(in_array($order->order_source, array('lazada'))){
					if(isset($selleruserids[$order->order_source][$order->selleruserid.'_@@_'.$order->order_source_site_id])){
						$order_shop_name = $selleruserids[$order->order_source][$order->selleruserid.'_@@_'.$order->order_source_site_id];
					}
				}
				
				if($order->order_source == 'wish'){
					//获取wish账号别名
					$selleruserids_new = \eagle\modules\platform\apihelpers\WishAccountsApiHelper::getWishAliasAccount(array($order->selleruserid=>$order->selleruserid));
					$order_shop_name = $selleruserids_new[$order->selleruserid];
				}
				
				//站点名
				$order_site_id = '';
				
				switch ($order->order_source){
					case 'amazon':
					case 'lazada':
					case 'shopee':
						$order_site_id = $order->order_source_site_id;
						break;
					default:
						$order_site_id = '';
				}
				
				//客选物流，发货类型
				$buyer_choose_shipping_method = '';
				if($order->order_source == 'aliexpress'){
					if(!empty($addi_info_arr)){
						if(isset($addi_info_arr['shipping_service'])){
							if(is_array($addi_info_arr['shipping_service'])){
								$tmp_shipping_service = $addi_info_arr['shipping_service'];
								//去除重复值
								$tmp_shipping_service = array_unique($tmp_shipping_service);
								
								$buyer_choose_shipping_method = implode(', ', $tmp_shipping_service);
							}
						}
					}
				}else if($order->order_source == 'cdiscount'){
					$cd_customer_shipped_method = \eagle\modules\carrier\helpers\CarrierHelper::getCdiscountBuyerShippingServices();
					
					$buyer_choose_shipping_method = isset($cd_customer_shipped_method[$order->order_source_shipping_method]) ? $cd_customer_shipped_method[$order->order_source_shipping_method] : $order->order_source_shipping_method;
				}else{
					$buyer_choose_shipping_method = $order->order_source_shipping_method;
				}
				
				//平台状态Icon
				$tmp_platform_state_icon = '';
				$tmp_platform_state_icon = self::getPlatformStatusV3($order);
				
				//买家评价
				$tmp_seller_comment = array('text'=>'', 'type'=>'');
				
				if(($order->order_source == 'ebay') && (!empty($order->seller_commenttext))){
					if(strtolower($order->seller_commenttype)=='positive')
						$tmp_seller_comment['type'] = '<img alt="Positive feedback rating" src="/images/ebay/iconPos_16x16.gif" width="16px">';
					if(strtolower($order->seller_commenttype)=='neutral')
						$tmp_seller_comment['type'] = '<img alt="Neutral feedback rating" src="/images/ebay/iconNeu_16x16.gif" width="16px">';
					if(strtolower($order->seller_commenttype)=='negative')
						$tmp_seller_comment['type'] = '<img alt="Negative feedback rating" src="/images/ebay/iconNeg_16x16.gif" width="16px">';
					
					$tmp_seller_comment['text'] = $order->seller_commenttext;
				}
				
				//物流跟踪信息 S
				$odOrderShipInfo = array();
				$tmp_order_ship_info = array();
				
				$div_event_html = '';
				
				if('sm' == $order->order_relation){
					$odOrderShipInfo = OrderHelper::getMergeOrderShippingInfo($order->order_id);
				}else{
					$odOrderShipInfo = $order->getTrackinfosPT();
				}
				
				//出口易处理号
				$tmp_to_deal_with = '';
				if($order['default_carrier_code'] == 'lb_chukouyi'){
					foreach ($odOrderShipInfo as $orderShipped){
						if($orderShipped['customer_number'] == $order['customer_number']){
							if(isset($orderShipped['return_no']['ItemSign'])){
								$tmp_to_deal_with = '<span aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="出口易处理号" title="">'.$orderShipped['return_no']['ItemSign'].'</span>';
							}
							break;
						}
					}
				}
				
				if(count($odOrderShipInfo)){
					foreach ($odOrderShipInfo as $ot_key => $ot){
						$class = 'text-info';
						$qtip = '';
						
						if ($ot->status==1){
							$class = 'text-success';
							$qtip = '<span qtipkey="tracking_number_with_non_error"></span>';
						}elseif ($ot->status==0){
							$class = 'text-warning';
							$qtip = '<span qtipkey="tracking_number_with_pending_status"></span>';
						}elseif($ot->status==2){
							$class = 'text-danger';
							$qtip = '<span qtipkey="tracking_number_with_error"></span>';
						}elseif($ot->status==4){
							$class='text-invalid';
							$qtip = '<span qtipkey="tracking_number_with_invalid_status"></span>';
						}
						
						if(!empty($ot->errors)){
							$tmp_order_ship_info[$ot_key]['errors'] = ($ot->addtype=='手动标记发货') ? '手动标记发货失败:' : '物流处理问题:'.$ot->errors;
						}
						
						if (strlen($ot->tracking_number)){
							$track_info = TrackingApiHelper::getTrackingInfo($ot->tracking_number);
							
							if ($track_info['success'] == true){
								$tmp_order_ship_info[$ot_key]['status'] = $track_info['data']['status'];
								
								
								//查询中  carrier_type 也等于0  , 但不是全球邮政
								if (isset($track_info['data']['carrier_type']) && ! in_array(strtolower($track_info['data']['status']) , ['checking',"查询中","查询等候中"]) ){
									if (isset(CarrierTypeOfTrackNumber::$expressCode[$track_info['data']['carrier_type']]))
										$tmp_order_ship_info[$ot_key]['carrier_type'] = "(".'通过'.CarrierTypeOfTrackNumber::$expressCode[$track_info['data']['carrier_type']].'查询到的结果'.")";
								}
							}
							
							$trackingOne = Tracking::find()->where(['track_no'=>$ot->tracking_number,'order_id'=>$order->order_source_order_id])
								->orderBy(['update_time'=>SORT_DESC])->one();
							
							if(!empty($trackingOne))
								$carrier_type = $trackingOne->carrier_type;
							else
								$carrier_type = '';
							
							if(!in_array($carrier_type, $non17Track)) 
								$tracking_info_type = '17track';
							else
								$tracking_info_type = '';
							
							$tmp_order_ship_info[$ot_key]['tracking_url'] = '<a href="javascript:void(0);" onclick="OrderCommon.OmsViewTracker(this,\''.$ot->tracking_number.'\',\''.$order->order_source.'\')" title="'.$ot->shipping_method_name.'" data-info-type="'.$tracking_info_type.'" ><span class="order-info"><font class="'.$class.'">'.$ot->tracking_number.'</font>'.$qtip.'</span></a>';
							
							//Tracker忽略物此流号操作	liang 16-02-27 start
							//当标记发货成功时，才出现忽略操作按钮
							if ($ot->status==1 && $order->logistic_status!=='ignored'){
								$tmp_order_ship_info[$ot_key]['ignored'] = '<span class="iconfont icon-ignore_search" onclick="OrderCommon.ignoreTrackingNo('.$order->order_id.',\''.$ot->tracking_number.'\')" data-toggle="popover" data-content="使物流查询助手忽略此物流号(不可逆操作)。当标记发货成功后，可选择此操作。忽略后，物流助手将不会再查询其信息" data-html="true" data-trigger="hover" data-placement="top" style="vertical-align:baseline;cursor:pointer;"></span>';
							}
							
							if( $order->logistic_status=='ignored'){
								$tmp_order_ship_info[$ot_key]['is_ignored'] = '<span style="color: #DCDCDC;cursor:pointer;"title="已经忽略物流信息查询">已忽略</span>';
							}
							
							//组织显示物流明细的东东
							$div_event_html .= "<div id='div_more_info_".$ot->tracking_number."' class='div_more_tracking_info div_space_toggle tracking_info_dialog_".$ot->tracking_number."'>";
							
							$all_events_str = "";
							
							$all_events_rt = TrackingHelper::generateTrackingEventHTML([$ot->tracking_number],[],$is_vip);
							if (!empty($all_events_rt[$ot->tracking_number])){
								$all_events_str = $all_events_rt[$ot->tracking_number];
							}
								
							$div_event_html .=  $all_events_str;
							
							$div_event_html .= "</div>";
							//组织显示物流明细的东东
						}
					}
				}
				//物流跟踪信息 E
				
				//订单相关icon S
				$tmp_order_related_icon = '';
				
				switch ($order->order_source){
					case 'cdiscount':
						if($order->order_type=='FBC')
							$tmp_order_related_icon .= " <span class='cd_fbc_inco_v3' title='Cdiscount FBC 订单'></span>";
				
						if(!empty($addi_info_arr['weird_FBC'])){
							$tmp_order_related_icon .= " <span class='iconfont icon-jinggao' title='价格偏低的FBC订单，有可能是问题订单，请谨慎处理' style='float:left;color:red;margin-right:5px;'></span>";
						}
				
						if($order->seller_commenttype=='Positive'){
							$tmp_order_related_icon .= ' <span style="background:green;"><a style="color: white" title="'.$order->seller_commenttext.'">好评</a></span>';
						}else if($order->seller_commenttype=='Neutral'){
							$tmp_order_related_icon .= ' <span style="background:yellow;"><a title="'.$order->seller_commenttext.'">中评</a></span>';
						}else if($order->seller_commenttype=='Negative'){
							$tmp_order_related_icon .= ' <span style="background:red;"><a title="'.$order->seller_commenttext.'">差评</a></span>';
						}
				
						if(!empty($order->weird_status)){
							$tmp_order_related_icon .= ' <div class="no-qtip-icon" style="display:inline;" qtipkey="cd_order_weird_status_'.$order->weird_status.'"><span class="cd-oms-weird-status-wfs-v3"></span></div>';
						}
				
						//订单客服状态icon
						if(empty($order->user_message) && !empty($order->issuestatus)){
							if($order->issuestatus=='IN_ISSUE'){
								$tmp_order_related_icon .= ' <div title="订单有申诉未关闭，您需要到CD后台处理完成后关闭该申诉" class="egicon-envelope-remove" style="float:left;margin:5px 5px 0 0;"></div>';
							}else if($order->issuestatus=='END_ISSUE'){
								$tmp_order_related_icon .= ' <div title="订单申诉已完成" class="egicon-envelope" style="float:left;margin:5px 5px 0 0;"></div>';
							}
						}
						break;
					case 'ebay':
						if ($order->order_verify == OdOrder::ORDER_VERIFY_VERIFIED){
							$tmp_order_related_icon = '<div title="paypal地址已同步" class="exception_221"></div>';
						}
						break;
					case 'amazon':
						if($order->order_type=='AFN'){
							$tmp_order_related_icon .= " <span class='label_custom label_o_custom' style='cursor: auto;'>FBA</span>";
							$is_no_delivery = true;
						}
						break;
					case 'jumia':
						if($order->order_type=='COD'){
							$tmp_order_related_icon .= " <span class='label_custom label_o_custom' style='cursor: auto;'>COD</span>";
						}
						break;
				}
				//订单相关icon E
				
				//操作 S
				$edit_operation = array();
				
				if ($order->order_source=="aliexpress"){
// 					$edit_operation['edit'] = '<a href=\''.(Url::to(['/order/aliexpressorder/edit','orderid'=>$order->order_id])).'\' target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;';
					$edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';
				}else if($order->order_source=="ebay"){
					$edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';
				 	
					if ($order->order_capture !='Y'){
						$edit_operation['edit'] .= '<a onclick="orderCommonV3.doactionone(\'givefeedback\',\''.$order->order_id.'\');" ><span class="egicon-appraise" title="评价"></span></a>&nbsp;'; 
						$edit_operation['edit'] .= '<a href="javascript:void(0)" onclick="orderCommonV3.sendmessage(\''.$order->order_id.'\',\''.$order->order_source.'\');" title="发送站内信"><span class="egicon-envelope2" aria-hidden="true"></span></a>&nbsp;';
						
						if($order->order_status == OdOrder::STATUS_NOPAY){
							$edit_operation['edit'] .= '<a href=\''.(Url::to(['/order/order/sendinvoice','orderid'=>$order->order_id])).'\' target="_blank"><span class="egicon-export2" title="发送账单"></span></a>&nbsp;';
							$edit_operation['edit'] .= '<a href="#" onclick="orderCommonV3.doactionone(\'signpayed\',\''.$order->order_id.'\')"><span class="egicon-bestoffer" title="标记为已付款"></span></a>&nbsp;';
						}
					}
				}else if($order->order_source=="amazon"){
// 					$edit_operation['edit'] = '<a href=\''.(Url::to(['/order/amazon-order/edit','orderid'=>$order->order_id])).'\' target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;';
					$edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';
					
				}else if($order->order_source=="cdiscount"){
					$edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';
					
				}else if($order->order_source=="bonanza"){
					//$edit_operation['edit'] = '<a href=\''.(Url::to(['/order/bonanza-order/edit','orderid'=>$order->order_id])).'\' target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;';
					$edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';
				
				}else if($order->order_source=="dhgate"){
					//$edit_operation['edit'] = '<a href=\''.(Url::to(['/order/dhgate-order/edit','orderid'=>$order->order_id])).'\' target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;';
					$edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';
					
				}else if($order->order_source=="ensogo"){
					//$edit_operation['edit'] = '<a href=\''.(Url::to(['/order/ensogo-order/edit','orderid'=>$order->order_id])).'\' target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;';
				    $edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';
					
				
				}else if($order->order_source=="jumia"){
					//$edit_operation['edit'] = '<a href=\''.(Url::to(['/order/jumia-order/edit','orderid'=>$order->order_id])).'\' target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;';
				    $edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';
					
				
				}else if($order->order_source=="lazada"){
					//$edit_operation['edit'] = '<a href=\''.(Url::to(['/order/lazada-order/edit','orderid'=>$order->order_id])).'\' target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;';
				    $edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';
					
				
				}else if($order->order_source=="linio"){
					//$edit_operation['edit'] = '<a href=\''.(Url::to(['/order/linio-order/edit','orderid'=>$order->order_id])).'\' target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;';
				    $edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';
					
				
				}else if($order->order_source=="priceminister"){
					//$edit_operation['edit'] = '<a href=\''.(Url::to(['/order/priceminister-order/edit','orderid'=>$order->order_id])).'\' target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;';
				    $edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';
					
					if(in_array($order->order_source_status,['new','current','tracking','claim'])){
						$edit_operation['edit'] .= '<a href="#" onclick="orderCommonV3.syncOneOrderStatus(\''.$order->order_id.'\')"><span class="glyphicon glyphicon-refresh toggleMenuL" style="top:1px" title="立即同步订单状态"></span></a>';
					}
				}else if($order->order_source=="wish"){
					//$edit_operation['edit'] = '<a href=\''.(Url::to(['/order/wish-order/edit','orderid'=>$order->order_id])).'\' target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;';
				    $edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';
					
				    
				}else if($order->order_source=="customized"){
					//$edit_operation['edit'] = '<a href=\''.(Url::to(['/order/customized-order/edit','orderid'=>$order->order_id])).'\' target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;';
				    $edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';
				
				}
				else if($order->order_source=="newegg"){
					//$edit_operation['edit'] = '<a href=\''.(Url::to(['/order/newegg-order/edit','orderid'=>$order->order_id])).'\' target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;';
					$edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';
				
				}else if($order->order_source=="shopee"){
					//$edit_operation['edit'] = '<a href=\''.(Url::to(['/order/lazada-order/edit','orderid'=>$order->order_id])).'\' target="_blank"><span class="glyphicon glyphicon-edit" title="查看/编辑订单详情"></span></a>&nbsp;';
				    $edit_operation['edit'] = '<a onclick="OrderCommon.editOrder(\''.$order->order_id.'\',this)"><span class="egicon-edit" title="编辑订单"></span></a>&nbsp;';

				}
				
				//操作选项
// 				$doarr = OrderHelper::getCurrentOperationList($current_order_status,'b');

				if($tmp_is_show == true){
					$do_operation_list_one = OrderHelper::getCurrentOperationList($order->order_status, 's');
				}else{
					$do_operation_list_one = OrderHelper::getCurrentOperationList($current_order_status, 's');
				}
				
				//CD订单特殊处理
				$tmp_is_show2 = $tmp_is_show;
				if($order->order_source == 'cdiscount'){
					if($order->issuestatus == 'IN_ISSUE'){
						unset($do_operation_list_one);
						$do_operation_list_one = OrderHelper::getCurrentOperationList($order->order_status, 's');
						$tmp_is_show2 = false;
					}
				}
				
				if($tmp_is_show2 == true){
					switch ($order->order_source){
						case 'ebay':
							if($order->order_status == OdOrder::STATUS_NOPAY){
								$do_operation_list_one +=['dispute'=>'催款取消eBay订单'];
							}else{
								$do_operation_list_one +=['givefeedback'=>'评价'];
							}
							
							if($order->order_status == OdOrder::STATUS_PAY){
								$do_operation_list_one +=['orderverifypass'=>'标记为paypal地址已同步'];
							}
							break;
						case 'cdiscount':
							$do_operation_list_one += ['invoiced' => '发票'];
							$do_operation_list_one += ['updateImage' => '更新图片缓存'];
							break;
						case 'amazon':
							$do_operation_list_one += ['invoiced' => '发票'];
							$do_operation_list_one += ['updateImage' => '更新图片缓存'];
							break;
						case 'aliexpress':
							$do_operation_list_one += ['invoiced' => '发票'];
							
							if($order->order_capture !== 'Y'){
								if (in_array($order->order_status, [Odorder::STATUS_WAITSEND, Odorder::STATUS_SHIPPED])){
									$do_operation_list_one += ['extendsBuyerAcceptGoodsTime'=>'延长买家收货时间'];
								}
							}
							break;
						case 'priceminister':
							$do_operation_list_one += ['invoiced' => '发票'];
							break;
						case 'lazada':
						case 'linio':
							$do_operation_list_one += ['invoiced' => '发票'];
							$do_operation_list_one += ['updateImage' => '更新图片缓存'];
							$do_operation_list_one += ['updateShipping' => '更新平台物流服务'];
							break;
						case 'wish':
							$do_operation_list_one += ['invoiced' => '发票'];
							break;
						case 'dhgate':
							$do_operation_list_one += ['invoiced' => '发票'];
							break;
						case 'jumia':
							$do_operation_list_one += ['invoiced' => '发票'];
							$do_operation_list_one += ['InvoiceDoprint' => 'Jumia官方发票打印'];
							break;
						case 'newegg':
							$do_operation_list_one += ['invoiced' => '发票'];
							$do_operation_list_one += ['updateImage' => '更新图片缓存'];
							break;
						case 'rumall':
							$do_operation_list_one += ['invoiced' => '发票'];
							break;
						default:
							break;
					}
					
					if($order->order_source != 'ebay'){
						unset($do_operation_list_one['givefeedback']);
					}
					
					if($order->order_capture !== 'Y'){
						if (in_array($order->order_status, [Odorder::STATUS_PAY, Odorder::STATUS_WAITSEND, Odorder::STATUS_SHIPPING, Odorder::STATUS_SHIPPED])){
							$do_operation_list_one += ['signshipped'=>'虚拟发货(标记发货)'];
						}
					}else{
						unset($do_operation_list_one['signshipped']);
					}
					
					if($order->order_status == OdOrder::STATUS_SUSPEND){
						$do_operation_list_one +=['reorder'=>'重新发货'];
					}
					
					if ($order->order_status !=OdOrder::STATUS_SHIPPED){
						$do_operation_list_one +=['copyOrder'=>'复制订单'];
					}
					
	// 				if ($order->order_status== OdOrder::STATUS_PAY)
	// 					$do_operation_list_one+=['getorderno'=>'移入发货中','outOfStock'=>'标记为缺货',];
					
					if ($order->order_status== OdOrder::STATUS_PAY)
						$do_operation_list_one+=['signwaitsend'=>'移入发货中','outOfStock'=>'标记为缺货',];
					
					
					//手工订单才可以有该选项 删除
					if(!(($order->order_capture == 'Y') && ($order->order_relation == 'normal'))){
						unset($do_operation_list_one['delete_manual_order']);
					}else{
						unset($do_operation_list_one['setSyncShipComplete']);
					}
					
					//lgw 作为测试用，指定user可以看到 20170508
	// 				if(\Yii::$app->subdb->getCurrentPuid()=='1'){
						if(!($order->order_capture == 'N' && $order->order_relation != 'fs')){
							unset($do_operation_list_one['split_order']);
						}
						
						if($order->order_relation != 'fs' && $order->order_relation != 'ss'){
							unset($do_operation_list_one['split_order_cancel']);
						}
	// 				}
	// 				else{
	// 					unset($do_operation_list_one['split_order']);
	// 					unset($do_operation_list_one['split_order_cancel']);
	// 				}
					
					if($is_no_delivery == true){
						unset($do_operation_list_one['setSyncShipComplete']);
						unset($do_operation_list_one['reorder']);
						unset($do_operation_list_one['signshipped']);
						unset($do_operation_list_one['ExternalDoprint']);
					}
					
					if(isset($do_operation_list_one['signshipped'])){
						//把“虚拟发货”放到第一位
						self::array_insert($do_operation_list_one, 1, ['signshipped'=>'虚拟发货(标记发货)']);
					}
					
					//PM未接受的订单，禁止部分操作
					if($order->order_source=='priceminister' && $order->order_source_status=='new'){
						if(isset($do_operation_list_one['cancelorder']))
							unset($do_operation_list_one['cancelorder']);
						if(isset($do_operation_list_one['signshipped']))
							unset($do_operation_list_one['signshipped']);
						if(isset($do_operation_list_one['suspendDelivery']))
							unset($do_operation_list_one['suspendDelivery']);
						if(isset($do_operation_list_one['signcomplete']))
							unset($do_operation_list_one['signcomplete']);
						if(isset($do_operation_list_one['setSyncShipComplete']))
							unset($do_operation_list_one['setSyncShipComplete']);
						if(isset($do_operation_list_one['setSyncShipComplete']))
							unset($do_operation_list_one['setSyncShipComplete']);
						if(isset($do_operation_list_one['signwaitsend']))
							unset($do_operation_list_one['signwaitsend']);
						if(isset($do_operation_list_one['outOfStock']))
							unset($do_operation_list_one['outOfStock']);
						if(isset($do_operation_list_one['reorder']))
							unset($do_operation_list_one['reorder']);
						if(isset($do_operation_list_one['ExternalDoprint']))
							unset($do_operation_list_one['ExternalDoprint']);
					}
					
					if(($order->order_status == OdOrder::STATUS_SHIPPED) && ($order->sync_shipped_status == 'F')){
						$do_operation_list_one +=['repulse_paid'=>'打回已付款'];
					}
				}
				
				$edit_operation['do_operation_list_one'] = $do_operation_list_one;
				//操作 E
				
				//生成打印/通知平台发货的图标
				$tmp_print_icon = '';
				
				$is_show_print_icon = false;
				
				if(in_array($order->order_status, array(OdOrder::STATUS_WAITSEND, OdOrder::STATUS_SHIPPING, OdOrder::STATUS_SHIPPED, OdOrder::STATUS_SUSPEND, OdOrder::STATUS_OUTOFSTOCK))){
					$is_show_print_icon = true;
				}
				
				if((in_array($order->sync_shipped_status, array('C','Y')))){
					$is_show_print_icon = true;
				}
				
				//不用发货操作的话，这些图标不要显示
				if($is_no_delivery == true){
					$is_show_print_icon = false;
				}
				
				if($is_show_print_icon == true){
					$tmp_print_icon .= '<span class="glyphicon glyphicon-duplicate margin_r10 '.(($order->is_print_distribution == 1) ? 'span_icon_green' : 'span_icon_gray').' " aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="'.(($order->is_print_distribution == 1) ? '已打印拣货单' : '未打印拣货单').'" title=""></span>';
					$tmp_print_icon .= '<span class="glyphicon glyphicon-print margin_r10 '.(($order->is_print_carrier == 1) ? 'span_icon_green' : 'span_icon_gray').'" aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" title="'.(($order->is_print_carrier == 1) ? '已打印面单' : '未打印面单').'"></span>';
					$tmp_print_icon .= '<span class="glyphicon glyphicon-saved margin_r10 '.((in_array($order->sync_shipped_status, array('C','Y'))) ? 'span_icon_green' : 'span_icon_gray').'" aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" title="'.((in_array($order->sync_shipped_status, array('C','Y'))) ? '已通知平台发货' : '未通知平台发货').'"></span>';
				}
				
				//利润统计权限
				$uid = \Yii::$app->user->id;
				$profix_permission = \eagle\modules\permission\apihelpers\UserApiHelper::checkOtherPermission('profix',$uid);
				
				$tmp_profits_info = '';
				if(in_array($order->order_source, array('cdiscount','ebay','aliexpress'))){
					if($profix_permission)
						$tmp_profits_info = self::getProfitsInfo($order);
				}
				
				
				//买家账号
				$tmp_buyer_user_account = '';
				if($order->order_source == 'ebay'){
					$tmp_buyer_user_account = $order->source_buyer_user_id;
				}
				
				//是否合并订单模式
				$group_order_md5 = '';
				if($isMergeOrder == 1){
					$group_order_md5 = md5(strtolower($order->selleruserid.$order->consignee.$order->consignee_address_line1.$order->consignee_address_line2.$order->consignee_address_line3));
				}
				
				$orderInfos[$order->order_id] = array(
						'platform_order_no' => $tmp_platform_order_no,	//平台订单号
						'order_id' => $order->order_id,	//小老板订单号
						'order_status' => $order->order_status,	//订单流程状态
						'order_source' => $order->order_source,	//订单来源
						'currency' => $order->currency,			//币种
						'desc' => $order->desc,					//订单备注
						'consignee' => $order->consignee,		//收货人
						'consignee_country_code' => $order->consignee_country_code,		//收货人国家代码
						'fulfill_deadline' => $order->fulfill_deadline,					//剩余发货时间
						'order_source_create_time' => $order->order_source_create_time,	//下单时间
						'paid_time' => $order->paid_time,								//付款时间
						'order_ship_time' => $order->order_ship_time, 					//通知平台发货时间 需要确认一下
						'complete_ship_time' => $order->complete_ship_time, 			//发货时间
						'default_shipping_method_code' => $order->default_shipping_method_code,	//默认运输服务id
						'default_carrier_code' => $order->default_carrier_code,			//默认物流商代码
						'user_message' => $order->user_message,							//付款备注
						'selleruserid' => $order->selleruserid,							//订单来源平台卖家用户名
						'source_buyer_user_id' => $order->source_buyer_user_id,			//来源买家用户名
						'carrier_error' => $order->carrier_error,						
						'carrier_step' => $order->carrier_step,							//物流操作步骤
						'seller_weight' => $order->seller_weight,						//卖家自己的称重重量(g)
						'order_capture' => $order->order_capture,						//是否手工订单N为否,Y为是
						'order_relation' => $order->order_relation,						//订单类型
						'exception_status' => $order->exception_status,					//检测异常状态
						'order_source_order_id' => $order->order_source_order_id,		//订单来源  的订单id
						'update_time' => $order->update_time,							//更新时间 
						'order_div_event_html' => $div_event_html,
						'customer_number' => $order->customer_number,
						'grand_total'=>$order->grand_total,
						'reorder_type'=>$order->reorder_type,
						
						'TagStr' => $TagStr,
						'amountInfoHtml' => $amountInfoHtml,
						'order_shop_name' => $order_shop_name,
						'order_site_id' => $order_site_id,
						'country_zh' => isset($countryList[$order->consignee_country_code]['cn']) ? $countryList[$order->consignee_country_code]['cn'] : '',
						'country_en' => isset($countryList[$order->consignee_country_code]['en_name']) ? $countryList[$order->consignee_country_code]['en_name'] : '',
						'timeLeft' => (((!empty($order->fulfill_deadline)) && (in_array($order->order_status , [OdOrder::STATUS_PAY , OdOrder::STATUS_WAITSEND  , OdOrder::STATUS_SHIPPING, OdOrder::STATUS_SUSPEND, OdOrder::STATUS_OUTOFSTOCK]))) ? $order->fulfill_deadline : '' ),
						'buyer_choose_shipping_method' => $buyer_choose_shipping_method,
						'warehouse_name' => isset($warehouses[$order->default_warehouse_id]) ? ($warehouses[$order->default_warehouse_id]) : '',
						'platform_state_icon' => $tmp_platform_state_icon,
						'last_message' => OrderFrontHelper::getLastMessage($order->order_source_order_id),	//买家留言
						'seller_comment' => $tmp_seller_comment, 				//买家评价
						'order_ship_info' => $tmp_order_ship_info,				//物流跟踪信息
						'edit_operation' => $edit_operation,					//操作编辑
						'print_icon' => $tmp_print_icon,						
						'profits_info' => $tmp_profits_info,
						'order_related_icon' => $tmp_order_related_icon,
						'order_item_is_empty' => self::getItemDeclarationIsEmpty($order, $result_item_declared_info),
						'buyer_user_account' => $tmp_buyer_user_account,
						'is_no_delivery' => $is_no_delivery,					
						'group_order_md5' => $group_order_md5,
						'to_deal_with' => $tmp_to_deal_with,	//货代处理号
				);
			 
				$orderInfos[$order->order_id]['items'] = array();
				
				if(count($order->getItemsPT()) > 0){
					foreach ($order->getItemsPT() as $key => $item){
						//额外信息
						$addi_info_item = $item->addi_info;
						if(!empty($addi_info_item))
							$addi_info_item = json_decode($addi_info_item,true);
						
						//CD平台特殊的Items 不显示
						if($order->order_source == 'cdiscount'){
							$nonDeliverySku = \eagle\modules\order\helpers\CdiscountOrderInterface::getNonDeliverySku();
							if(empty($item->sku) or in_array(strtoupper($item->sku),$nonDeliverySku) ) continue;
						}
						
						//产品URL
						$product_name_url = self::getOrderProductUrl($order, $item);
// 						switch ($order->order_source){
// 							case 'ebay':
// 								$product_name_url = in_array($order->order_source_site_id,$siteEbayList)?$siteEbayUrl[$order->order_source_site_id].$item->order_source_itemid:$item->product_url;
// 								break;
// 							case 'aliexpress':
// 								$product_name_url = "https://www.aliexpress.com/item/xxx/".$item->order_source_itemid.".html";
// 								break;
// 							case 'amazon':
// 								$tmpurl = "http://www.amazon.";
// 								$tmpplace=strtolower($order->order_source_site_id);
// 								if ($tmpplace=='jp'||$tmpplace=='uk') {
// 									$tmpurl .='co.'.$tmpplace;
// 								}else if ($tmpplace=='mx'||$tmpplace=='br'||$tmpplace=='au') {
// 									$tmpurl .='com.'.$tmpplace;
// 								}else if ($tmpplace=='us') {
// 									$tmpurl .='com';
// 								}else{
// 									$tmpurl .=$tmpplace;
// 								}
// 								$tmpurl .= "/gp/product/".$item->order_source_itemid;
								
// 								$product_name_url = $tmpurl;
// 								break;
// 							default:
// 								$product_name_url = $item->product_url;
// 						}
						//2017-02-17 liang------
						$photo_primary_url = $item->photo_primary;
						
						//当设置linio使用已配对SKU对应的商品库图片
						// dzt20190710 for 导入订单显示匹配到产品的产品图片，待优化，导入订单对匹配到的产品填上图片之后可以去掉这个
						if(in_array($order->order_source, ['linio']) || $order->order_capture == "Y"){
						    if(!empty($item->root_sku) && !empty($order_rootsku_product_image[$order->order_id][$item->root_sku]))
						        $photo_primary_url = $order_rootsku_product_image[$order->order_id][$item->root_sku];
						}
						
						if($order->order_source=='cdiscount'){
							$photo_primary_url = CdiscountOrderInterface::getCdiscountOrderItemPhotoForBrowseShow($item,$puid);
						}else if($order->order_source=='priceminister')
							$photo_primary_url =\eagle\modules\util\helpers\ImageCacherHelper::getImageCacheUrl($item->photo_primary, $puid, 1);
						else if(in_array($order->order_source,['lazada','jumia'])){
							if(!empty($photo_primary_url)){
								$big_photo_primary_url = str_replace("-catalog", "", $photo_primary_url);// 去掉 -catalog
							}
						}else if($order->order_source=='amazon'){
							$big_photo_primary_url = str_replace("160_.jpg","500_.jpg",$photo_primary_url);
						}
						
						$orderInfos[$order->order_id]['items'][$key]['photo_primary_url'] = $photo_primary_url;
						$orderInfos[$order->order_id]['items'][$key]['big_photo_primary_url'] = empty($big_photo_primary_url) ? $photo_primary_url : $big_photo_primary_url;
						//----------------------
						$orderInfos[$order->order_id]['items'][$key]['sku'] = $item->sku;
						$orderInfos[$order->order_id]['items'][$key]['quantity'] = $item->quantity;
						$orderInfos[$order->order_id]['items'][$key]['price'] = $item->price;
						$orderInfos[$order->order_id]['items'][$key]['product_name'] = $item->product_name;
						
						if(in_array($order->order_source,['ebay'])){
							$orderInfos[$order->order_id]['items'][$key]['order_source_srn'] = $item->order_source_srn;
						}else{
							$orderInfos[$order->order_id]['items'][$key]['order_source_order_id'] = $item->order_source_order_id;
						}
						
						//产品URL
						$orderInfos[$order->order_id]['items'][$key]['product_name_url'] = $product_name_url;
						
						//产品属性
						//ebay的product_attributes有可能过长，需要特殊处理lwj20180606
						if($order->order_source == 'ebay'){
						    $tmpProdctAttrbutes = json_decode($item->product_attributes,true);
						    if(is_array($tmpProdctAttrbutes)){
						        $orderInfos[$order->order_id]['items'][$key]['product_attributes_arr'] = self::getProductAttributesByPlatformItem($order->order_source, $item->product_attributes);
						    }else if(!empty($item->addi_info)){
						        $addi_info = json_decode($item->addi_info,true);
						        if(isset($addi_info['product_attributes'])){
						            $orderInfos[$order->order_id]['items'][$key]['product_attributes_arr'] = self::getProductAttributesByPlatformItem($order->order_source, $addi_info['product_attributes']);
						        }else{
						            $orderInfos[$order->order_id]['items'][$key]['product_attributes_arr'] = self::getProductAttributesByPlatformItem($order->order_source, '');
						        }
						    }
						}else{
						    $orderInfos[$order->order_id]['items'][$key]['product_attributes_arr'] = self::getProductAttributesByPlatformItem($order->order_source, $item->product_attributes);
						}
						
						
						//采购链接
						//$orderInfos[$order->order_id]['items'][$key]['purchase_link'] = empty($skuListInfo[$item->root_sku]['purchase_link']) ? '' : $skuListInfo[$item->root_sku]['purchase_link'];
						$orderInfos[$order->order_id]['items'][$key]['purchase_link'] = '';
						$orderInfos[$order->order_id]['items'][$key]['purchase_link_list'] = '';
						if(array_key_exists($item->root_sku, $pd_sp_list)){
							$orderInfos[$order->order_id]['items'][$key]['purchase_link'] = $pd_sp_list[$item->root_sku]['purchase_link'];
							$orderInfos[$order->order_id]['items'][$key]['purchase_link_list'] = json_encode($pd_sp_list[$item->root_sku]['list']);
						}
						
						//是否有商品
						$orderInfos[$order->order_id]['items'][$key]['is_product'] = empty($skuListInfo[$item->root_sku]) ? false : true;
						
						//订单商品ID
						$tmp_platform_source_item_id = '';
						//item 对应平台状态 S
						$tmp_platform_status_item = '';
						//item Html 状态1 暂时不清楚边几个平台有这个状态所以暂时定义了一个1
						$tmp_platform_item_html = '';
						
						if($order->order_source == 'priceminister'){
							if(!empty($item->order_source_order_item_id)){
								if(!empty($item['source_item_id']))
									$tmp_platform_source_item_id = '<span aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="订单商品id" title="" >'.$item['source_item_id'].'</span>';
								
								if(!empty($item->platform_status)){
									$tmp_platform_status_item = $item->platform_status;
									$tmp_platform_item_html .= '<span aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="商品状态" title="" >'.$item->platform_status.'</span>';
								}
								
								if(($tmp_platform_status_item=='TO_CONFIRM'|| $tmp_platform_status_item=='REQUESTED' || $tmp_platform_status_item=='REMINDED') && empty($addi_info_item['userOperated'])){
									$tmp_platform_item_html .= '<button type="button" class="btn-info button_pm_item" onclick="pmOrder.list.operateNewSaleItem(\'accept\',\''.$item['source_item_id'].'\',\''.$order->selleruserid.'\')">接受</button><button type="button" class="btn-danger button_pm_item" onclick="pmOrder.list.operateNewSaleItem(\'refuse\',\''.$item['source_item_id'].'\',\''.$order->selleruserid.'\')">拒接</button>';
								}
								
								if(($tmp_platform_status_item=='TO_CONFIRM'|| $tmp_platform_status_item=='REQUESTED' || $tmp_platform_status_item=='REMINDED') && !empty($addi_info_item['isNewSale']) && !empty($addi_info_item['userOperated'])){
									$tmp_platform_item_html .= '<br><b>已经做过接受/拒接操作，请耐心等待同步</b>';
									if(!empty($addi_info_item['operate_time']))
										$tmp_platform_item_html .= '<br>操作时间:'.$addi_info_item['operate_time'];
								}
							}
						}else if($order->order_source == 'lazada'){
							if (!empty($item->platform_status)){
								if(in_array($item->platform_status, \eagle\modules\lazada\apihelpers\LazadaApiHelper::$CANNOT_SHIP_ORDERITEM_STATUS)){
									$tmp_platform_item_html = ' <span aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="lazada item状态" title="" style="color: red;">'.$item->platform_status.'</span>';
								}else{
									$tmp_platform_item_html = ' <span aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="lazada item状态" title="" >'.$item->platform_status.'</span>';
								}
							}
						}else if($order->order_source == 'linio'){
							if (!empty($item->platform_status)){
								$tmp_platform_item_html = ' <span aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="linio item状态" title="" >'.$item->platform_status.'</span>';
							}
						}else if($order->order_source == 'bonanza'){
							$tmp_platform_source_item_id = '<span aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="ItemID" title="" >'.$item['order_source_itemid'].'</span>';
						}
						else if($order->order_source == 'wish'){
						    if(!empty($item['order_source_itemid']))
							    $tmp_platform_source_item_id = '<span aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="ItemID" title="" >'.$item['order_source_itemid'].'</span>';
						}
						else if($order->order_source == 'jumia'){
							if (!empty($item->platform_status)){
								$tmp_platform_item_html = ' <span aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="jumia item状态" title="" >'.$item->platform_status.'</span>';
							}
						}
						else if($order->order_source == 'rumall'){
							if (!empty($item->order_source_itemid)){
								$tmp_platform_item_html = ' <span aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="rumall item状态" title="" >'.$item->order_source_itemid.'</span>';
							}
						}
						
						$orderInfos[$order->order_id]['items'][$key]['platform_source_item_id'] = $tmp_platform_source_item_id;
						$orderInfos[$order->order_id]['items'][$key]['platform_item_html'] = '<p>'.$tmp_platform_item_html.'</p>';
						
						//item 对应平台状态 E
						
						//是否显示root_sku
						$orderInfos[$order->order_id]['items'][$key]['show_root_sku'] = ($item->root_sku == '') ? '' : '('.$item->root_sku.')';
						
						$orderInfos[$order->order_id]['items'][$key]['used_stock_info'] = '';
						
						if($is_show_AvailableStock == false){
							if(($item->root_sku != '') && (in_array($order->order_status, array(200, 300)))){
								$orderInfos[$order->order_id]['items'][$key]['used_stock_info'] = '<span class="used_stock_info">（可用库存: '.(empty($stock_list[$order->default_warehouse_id][$item->root_sku]) ? '0' : $stock_list[$order->default_warehouse_id][$item->root_sku]).'）</span>';
							}
						}
						
// 						if($item->root_sku != ''){
// 							if($item->sku != $item->root_sku){
// 								$orderInfos[$order->order_id]['items'][$key]['show_root_sku'] = '('.$item->root_sku.')';
// 							}
// 						}

						//是否尖货
						if(!empty($addi_info_item) && !empty($addi_info_item['sendGoodsOperator'])){
							if($addi_info_item['sendGoodsOperator'] == 'WAREHOUSE_SEND_GOODS'){
								$orderInfos[$order->order_id]['items'][$key]['is_warehouse_send'] = true;
							}
						}
					}
				}
			}
		}
		
		return $orderInfos;
	}
	
	//封装 编辑订单 金额 显示的HTML 
	public static function getDisplayOrderAmountInfoHTML($order){
		$tmp_html = '';
		
		switch (true){
			case in_array($order->order_source,['cdiscount']):
				$currencySing = $order->currency;
				$currencyInfo = StandardConst::getCurrencyInfoByCode($currencySing);
				if(!empty($currencyInfo) && !empty($currencyInfo['html'])){
					$currencySing = $currencyInfo['html'];
				}
				$tmp_html = '<span>产品+'.$order->subtotal.' '.$currencySing.'</span>'.
					'<span class="list_order_span_7">佣金-'.(!empty($order->commission_total)?$order->commission_total:$order->discount_amount).' '.$currencySing.'</span>'.
					'<span class="list_order_span_7">运费+'.$order->shipping_cost.' '.$currencySing.'</span>'.
					'<span class="list_order_span_7">合计='.$order->grand_total.' '.$currencySing.'</span>';
				break;
				case in_array($order->order_source,['priceminister']):
					$currencySing = $order->currency;
					$currencyInfo = StandardConst::getCurrencyInfoByCode($currencySing);
					if(!empty($currencyInfo) && !empty($currencyInfo['html'])){
						$currencySing = $currencyInfo['html'];
					}
					$tmp_html = '<span>产品+'.$order->subtotal.' '.$currencySing.'</span>'.
// 						'<span class="list_order_span_7">运费+'.$order->shipping_cost.' '.$currencySing.'</span>'.
						'<span class="list_order_span_7">合计='.$order->grand_total.' '.$currencySing.'</span>';
				break;
			case in_array($order->order_source,['ebay']):
				if (!empty($order->addi_info)){
					$addi_info = json_decode($order->addi_info,true);
				}
				$currencySing = $order->currency;
				$tmp_html = '<span>总价: '.$order->grand_total.' '.$currencySing.'</span>'.
					'<span class="list_order_span_7">佣金: '.((!empty($order->commission_total)?$order->commission_total:0)).' '.(!empty($addi_info['FinalValueFeeCurrency'])?$addi_info['FinalValueFeeCurrency']:'USD').'</span>'.
					'<span class="list_order_span_7">PP费: '.(!empty($order->paypal_fee)?$order->paypal_fee:0).' '.(!empty($addi_info['FeeOrCreditAmountCurrency'])?$addi_info['FeeOrCreditAmountCurrency']:$currencySing).'</span>';
				break;
			case in_array($order->order_source,['lazada', 'linio', 'jumia']):
// 				subtotal + shipping_cost - discount_amount = grand_total

				$currencySing = $order->currency;
				
				$tmp_html = '<span>产品+'.$order->subtotal.' '.$currencySing.'</span>'.
						'<span class="list_order_span_7">运费+'.$order->shipping_cost.' '.$currencySing.'</span>'.
						'<span class="list_order_span_7">折扣-'.$order->discount_amount.$currencySing.'</span>'.
						'<span class="list_order_span_7">合计='.$order->grand_total.' '.$currencySing.'</span>';
				
				/*
				$tmp_html = '<span>总价: '.$order->grand_total.' '.$order->currency.'</span> ';
				*/
				
				$tmp_usd = \common\helpers\Helper_Currency::convertThisCurrencyToUSD($order->currency, $order->grand_total);
				//获取最新汇率，转换USD
				$EXCHANGE_RATE = ProfitHelper::GetExchangeRate($order->currency, "USD", true);
				if(!empty($EXCHANGE_RATE)){
					$tmp_usd = $EXCHANGE_RATE * $order->grand_total;
				}
				
				if($tmp_usd != -1){
					$tmp_html .= '<span class="span_simsun_100">「'.sprintf("%.2f", $tmp_usd).' USD';
					$tmp_html .= '<span qtipkey="order_amount_to_USD"></span>」</span>';
				}
				break;
			case in_array($order->order_source,['shopee']):
				$currencySing = $order->currency;
			
				$tmp_html = '<span>产品+'.$order->subtotal.' '.$currencySing.'</span>'.
						'<span class="list_order_span_7">运费+'.$order->shipping_cost.' '.$currencySing.'</span>'.
						'<span class="list_order_span_7">合计='.$order->grand_total.' '.$currencySing.'</span>';
			
				$tmp_usd = \common\helpers\Helper_Currency::convertThisCurrencyToUSD($order->currency, $order->grand_total);
				//获取最新汇率，转换USD
				$EXCHANGE_RATE = ProfitHelper::GetExchangeRate($order->currency, "USD", true);
				if(!empty($EXCHANGE_RATE)){
					$tmp_usd = $EXCHANGE_RATE * $order->grand_total;
				}
			
				if($tmp_usd != -1){
					$tmp_html .= '<span class="span_simsun_100">「'.sprintf("%.2f", $tmp_usd).' USD';
					$tmp_html .= '<span qtipkey="order_amount_to_USD"></span>」</span>';
				}
				break;
			default:
				//共用部分
				$tmp_html = '<span>总价: '.$order->grand_total.' '.$order->currency.'</span>';
				break;
		}
		
		return $tmp_html;
	}
	
	//根据平台来解释产品属性
	public static function getProductAttributesByPlatformItem($platform, $product_attributes){
		$tmp_product_attributes_arr = array();
		
		if(empty($product_attributes)){
			return $tmp_product_attributes_arr;
		}
		
		switch ($platform){
			case 'ebay':
			case 'wish':
				$tmpProdctAttrbutes = json_decode($product_attributes, true);
				if (is_array($tmpProdctAttrbutes)){
					foreach($tmpProdctAttrbutes as $_tmpAttr){
						if (is_array($_tmpAttr)){
							foreach($_tmpAttr as $_tmpAkey=>$_tmpAValue){
								$tmp_product_attributes_arr[] = $_tmpAkey.":".$_tmpAValue;
							}
						}else{
							$tmp_product_attributes_arr[] = $_tmpAttr;
						}
					}
				}
				break;
			case 'aliexpress':
			case 'dhgate':
			case 'jumia':
			case 'shopee':
				$tmpProdctAttrbutes = explode(' + ' ,$product_attributes );
				if (!empty($tmpProdctAttrbutes)){
					foreach($tmpProdctAttrbutes as $_tmpAttr){
						$tmp_product_attributes_arr[] = $_tmpAttr;
					}
				}
				break;
			default:
				$tmp_product_attributes_arr = array();
		}
		
		return $tmp_product_attributes_arr;
	}
	
	//获取ebay状态图标
	public static function getPlatformStatusV3($order){
		$tmp_platform_status = '';
		
		if($order->order_source == 'ebay'){
			$ebayStatusIcon = array(
					'checkoutstatus' => array(),
					'pay_status' => array(),	//付款状态图标
					'shipping_status' => array()	//发货图标
			);
			
			$OrderSourceOrderIdList = [];
			$OrderSourceOrderIdList[] = $order->order_source_order_id;
				
			//获取当前  check out 状态
			$orderCheckOutList = [];
			$orderCheckOutList = OrderApiHelper::getEbayCheckOutInfo($OrderSourceOrderIdList);
			
			//<!-- check图标 -->
			if (!empty($orderCheckOutList[$order->order_source_order_id])){
				$check = $orderCheckOutList[$order->order_source_order_id];
			}else{
				$check = [];
			}
			
			if((!empty($check)) && ($check['checkoutstatus']=='Complete')){
				$ebayStatusIcon['checkoutstatus'] = array('title'=>'已结款', 'class'=>'sprite_check_1');
			}else{
				$ebayStatusIcon['checkoutstatus'] = array('title'=>'未结款', 'class'=>'sprite_check_0');
			}
			
			//付款状态图标
			if($order->pay_status == 0){
				$ebayStatusIcon['pay_status'] = array('title'=>'未付款', 'class'=>'sprite_pay_0');
			}else if($order->pay_status == 1){
				$ebayStatusIcon['pay_status'] = array('title'=>'已付款', 'class'=>'sprite_pay_1');
			}else if($order->pay_status == 2){
				$ebayStatusIcon['pay_status'] = array('title'=>'支付中', 'class'=>'sprite_pay_2');
			}else if($order->pay_status == 3){
				$ebayStatusIcon['pay_status'] = array('title'=>'已退款', 'class'=>'sprite_pay_3');
			}
			
			//发货图标
			if ($order->shipping_status==1){
				$ebayStatusIcon['shipping_status'] = array('title'=>'已发货', 'class'=>'sprite_shipped_1');
			}else{
				$ebayStatusIcon['shipping_status'] = array('title'=>'未发货', 'class'=>'sprite_shipped_0');
			}
			
			$tmp_platform_status = $ebayStatusIcon;
		}else if($order->order_source == 'aliexpress'){
			if (isset(OdOrder::$aliexpressStatus[$order->order_source_status])){
				$tmp_platform_status = OdOrder::$aliexpressStatus[$order->order_source_status];
			}else{
				$tmp_platform_status = $order->order_source_status;
			}
		}else if($order->order_source == 'amazon'){
			$tmp_platform_status = $order->order_source_status;
		}else if($order->order_source == 'cdiscount'){
			if(!empty($order->order_source_status)){
				if(isset(CdiscountOrderHelper::$cd_source_status_mapping[$order->order_source_status]))
					$tmp_platform_status = CdiscountOrderHelper::$cd_source_status_mapping[$order->order_source_status];
				else
					$tmp_platform_status = $order->order_source_status;
			}
		}else if($order->order_source == 'priceminister'){
			if(!empty($order->order_source_status)){
				$source_status_mapping = \eagle\modules\order\helpers\PriceministerOrderHelper::$orderStatus;
				if(!empty($source_status_mapping[$order->order_source_status]))
					$tmp_platform_status = $source_status_mapping[$order->order_source_status];
				else
					$tmp_platform_status = $order->order_source_status;
			}
		}else if(in_array($order->order_source, array('lazada','linio','jumia'))){
			$search_order_source_status = array();
			foreach (array_keys(\eagle\modules\lazada\apihelpers\SaasLazadaAutoFetchApiHelper::$LAZADA_EAGLE_ORDER_STATUS_MAP) as $source_status){
				$search_order_source_status[$source_status] = $source_status;
			}
			
			if (isset($search_order_source_status[$order->order_source_status])){
				$tmp_platform_status = $search_order_source_status[$order->order_source_status];
			}else{
				$tmp_platform_status = $order->order_source_status;
			};
		}else if($order->order_source == 'bonanza'){
			$tmp_bonanza_status = \eagle\modules\order\helpers\BonanzaOrderHelper::$bn_source_status_mapping;
			
			if(!empty($order->order_source_status)){
				$order_status_array = explode(',', $order->order_source_status);
				
				if(count($order_status_array)>1){
					unset($tmp_platform_status);
					$tmp_platform_status = array();
					
					if(isset($tmp_bonanza_status['checkoutStatus'][$order_status_array[0]]))
						$tmp_platform_status[] = array('title'=>'checkoutStatus', 'status'=>$tmp_bonanza_status['checkoutStatus'][$order_status_array[0]]);
					else
						$tmp_platform_status[] = array('title'=>'checkoutStatus', 'status'=>$order_status_array[0]);
					
					if(isset($tmp_bonanza_status['orderStatus'][$order_status_array[1]]))
						$tmp_platform_status[] = array('title'=>'orderStatus', 'status'=>$tmp_bonanza_status['orderStatus'][$order_status_array[1]]);
					else
						$tmp_platform_status[] = array('title'=>'orderStatus', 'status'=>$order_status_array[1]);
				}else{
					$tmp_platform_status = $order->order_source_status;
				}
			}
		}else if($order->order_source == 'wish'){
			$tmp_platform_status = $order->order_source_status;
		}else if($order->order_source == 'dhgate'){
			if(!empty($order->order_source_status)){
				if(isset(QueueDhgateGetorder::$orderStatus[$order->order_source_status]))
					$tmp_platform_status = $order->order_source_status."(".QueueDhgateGetorder::$orderStatus[$order->order_source_status].")";
				else
					$tmp_platform_status = $order->order_source_status;
			}
		}else if($order->order_source == 'newegg'){
			$search_order_source_status = array();
			foreach (array_keys(\console\helpers\SaasNeweggAutoFetchApiHelper::$NEWEGG_EAGLE_ORDER_STATUS_MAP) as $source_status){
				$search_order_source_status[$source_status] = $source_status;
			}
			
			if (isset($search_order_source_status[$order->order_source_status])){
				$tmp_platform_status = $search_order_source_status[$order->order_source_status];
			}else{
				$tmp_platform_status = $order->order_source_status;
			};
		}else if($order->order_source == 'rumall'){
			$tmp_platform_status = $order->order_source_status;
		}else if($order->order_source == 'shopee'){
			if (isset(ShopeeOrderHelper::$shopeeStatus[$order->order_source_status])){
				$tmp_platform_status = ShopeeOrderHelper::$shopeeStatus[$order->order_source_status];
			}else{
				$tmp_platform_status = $order->order_source_status;
			}
		}
		
		
		return $tmp_platform_status;
	}
	
	//利润计算  相关的html
	public static function getProfitsInfo($order){
		$tmp_profits_info = '';
		
		$currencySing = $order->currency;
		$color = 'red';
		
		if(!is_null($order->profit)){
			if(floatval($order->profit) > 0) $color = 'green';
			
			if(!empty($order->addi_info)){
				$addi_info = json_decode($order->addi_info,true);
				$exchange_rate = empty($addi_info['exchange_rate'])?'--':$addi_info['exchange_rate'];
				$exchange_loss = empty($addi_info['exchange_loss'])?'--':$addi_info['exchange_loss'];
				$product_cost = empty($addi_info['product_cost'])?'--':$addi_info['product_cost'];
				$logistics_cost = empty($order->logistics_cost) ? (empty($addi_info['logistics_cost']) ? '--' : $addi_info['logistics_cost']) : $order->logistics_cost;
				$profit_detail = '商品成本:'.$product_cost.'<br>物流成本:'.$logistics_cost.'<br>销售金额:'.$order->grand_total.$currencySing.'<br>货币汇率:'.$exchange_rate.'<br>汇损:'.$exchange_loss.'%'.'<br>计算公式:利润(RMB)=订单销售额*汇率*（1-汇损）-商品总成本-订单物流成本';
				$tmp_profits_info .= "<span class='profit_detail_v3' data-profit='".$order->profit."' data-toggle='popover' data-content='".$profit_detail."' data-html='true' data-trigger='hover' style='color:".$color."'>";
			}else{
				$tmp_profits_info .= "<span style='font-weight:bold;'>";
			}
			
			$tmp_profits_info .= "利润：".$order->profit.'</span>';
		}else{
			if($order->order_source == 'aliexpress'){
				$tmp_profits_info .= '<span style="color:#FFB0B0" title="请到统计模块计算利润">未计算利润</span>';
			}else{
				$tmp_profits_info .= '<span style="color:#FFB0B0" title="勾选订单后可于批量操作中计算利润">未计算利润</span>';
			}
		}
		
		return $tmp_profits_info;
	}
	
	//根据订单的items 信息获取相关商品档案的数据
	public static function getOrderSkuListInfo($order_models){
		//订单商品信息
		$sku_List = [];
		foreach ($order_models as $model){
			$order_items = $model->getItemsPT();
			foreach ($order_items as $item){
				if($item->root_sku != '')
					$sku_List[] = $item->root_sku;
			}
		}
		$sku_List = array_unique($sku_List);
		$product_infos = [];
		$product_models = Product::find()->where(['sku'=>$sku_List])->asArray()->all();
		
		if(count($product_models) > 0){
			foreach ($product_models as $prod_model){
				$product_infos[$prod_model['sku']]=$prod_model;
			}
		}
		
		return $product_infos;
	}
	
	//获取报关信息是否为空
	public static function getItemDeclarationIsEmpty($order, $result_item_declared_info){
		$declarationIsEmpty = 1;
		
		if($order->order_status != OdOrder::STATUS_PAY){
			return $declarationIsEmpty;
		}
		
		if(($order->order_source =='cdiscount') && ($order->order_type == 'FBC')){
			return $declarationIsEmpty;
		}
		
		//是否全局的报关信息
		$is_all_declaration = false;
		
		$isExistProduct = true;
		foreach($order->getItemsPT() as $item){
			if($order->order_source == 'cdiscount'){
				$nonDeliverySku = \eagle\modules\order\helpers\CdiscountOrderInterface::getNonDeliverySku();
				if(empty($item->sku) or in_array(strtoupper($item->sku),$nonDeliverySku) ) continue;
			}
			
			if(isset($result_item_declared_info[$item->order_item_id]['not_declaration'])){
				if($result_item_declared_info[$item->order_item_id]['not_declaration'] == 2){
					$is_all_declaration = true;
				}
			}
			
			if(!empty($result_item_declared_info[$item->order_item_id]['not_declaration'])){
				if($result_item_declared_info[$item->order_item_id]['not_declaration'] == 2){
					continue;
				}
				
				$isExistProduct = false;
				break;
			}
		}
		
		if($isExistProduct == false){
			$declarationIsEmpty = 2;
		}
		
		//假如不为空则需要判断是否存在直接读取了全局报关信息，全局报关信息需要做其它提示
		if($declarationIsEmpty == 1){
			if($is_all_declaration == true){
				$declarationIsEmpty = 3;
			}
		}
			
		return $declarationIsEmpty;
	}
	
	/**
	 * [array_insert 插入到数组指定位置]
	 */
	static public function array_insert(&$array, $position, $insert_array){
		$first_array = array_splice ($array, 0, $position);
		$array = array_merge ($first_array, $insert_array, $array);
	}
	
	/**
	 * 生成 新版 DropdownList 的html方法
	 * 
	 * $prompt			提示label
	 * $actionItems		
	 * 					示例1：$excelEvent = array('0'=>array('event'=>'OrderCommon.orderExcelprint(0)','label'=>'按勾选导出'), '1'=>array('event'=>'OrderCommon.orderExcelprint(1)','label'=>'按所有页导出'));
	 * 					示例2：Array('addMemo' => '添加备注', 'setSyncShipComplete' => '标记为提交成功', 'reorder' => '已出库订单补发', 'signshipped' => '虚拟发货(标记发货)', 'ExternalDoprint' => '打印面单', 'cancelorder' => '取消订单', 'invoiced' => '发票')
	 * $actionEvent		当为空时使用示例1, 不为空时需要填上事件名：orderCommonV3.doaction3
	 */ 
	public static function getDropdownToggleHtml($prompt, $actionItems, $actionEvent = ''){
		$actionHtmls = '';
		
		foreach ($actionItems as $actionItemKey => $actionItemVal){
			if($actionEvent == ''){
				if(isset($actionItemVal['is_combined'])){
// 					$tmp_html = '<span style="float:right;font-size: 10px;line-height: 20px;color: red;margin-right:5px;cursor:pointer;" class="glyphicon glyphicon-remove" onclick="'.$actionItemVal['combined_event'].'"></span>';
// 					$actionHtmls .= '<li><a href="#" style="float:left;min-width: 130px;" onclick="'.$actionItemVal['event'].'" >'.$actionItemVal['label'].'</a>'.$tmp_html.'</li>';

					$tmp_html = '<span style="position: absolute;right: 5px;font-size: 10px;line-height: 20px;color: red;cursor:pointer;" class="glyphicon glyphicon-remove" onclick="'.$actionItemVal['combined_event'].'"></span>';
					$actionHtmls .= '<li style="position: relative;"><a href="#" style="" onclick="'.$actionItemVal['event'].'" >'.$actionItemVal['label'].'</a>'.$tmp_html.'</li>';
				}else{
					$actionHtmls .= '<li><a href="#" onclick="'.$actionItemVal['event'].'" >'.$actionItemVal['label'].'</a>'.'</li>';
				}
			}else{
				if($actionItemKey == '')
					continue;
					
				$actionHtmls .= '<li><a href="#" onclick="'.$actionEvent.'(\''.$actionItemKey.'\')'.'" >'.$actionItemVal.'</a></li>';
			}
		}
		
		$html = '<div class="btn-group">'.
				'<button type="button" class="iv-btn btn-important dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'.$prompt.'&nbsp;'.
				'<span class="caret"></span></button><ul class="dropdown-menu" style="overflow: auto;max-height: 350px;">'.$actionHtmls.'</ul></div>';
		
		return $html;
	}
	
	//获取产品URL
	public static function getOrderProductUrl($order, $item){
		//listing 站点前缀
		$siteEbayUrl = \common\helpers\Helper_Siteinfo::getSiteViewUrl();
		$siteEbayList = \common\helpers\Helper_Siteinfo::getSite();
		
		$product_name_url = '';
		
		switch ($order->order_source){
			case 'ebay':
				$product_name_url = in_array($order->order_source_site_id,$siteEbayList)?$siteEbayUrl[$order->order_source_site_id].$item->order_source_itemid:$item->product_url;
				break;
			case 'aliexpress':
				$product_name_url = "https://www.aliexpress.com/item/xxx/".$item->order_source_itemid.".html";
				break;
			case 'amazon':
				$tmpurl = "http://www.amazon.";
				$tmpplace=strtolower($order->order_source_site_id);
				if ($tmpplace=='jp'||$tmpplace=='uk') {
					$tmpurl .='co.'.$tmpplace;
				}else if ($tmpplace=='mx'||$tmpplace=='br'||$tmpplace=='au') {
					$tmpurl .='com.'.$tmpplace;
				}else if ($tmpplace=='us') {
					$tmpurl .='com';
				}else{
					$tmpurl .=$tmpplace;
				}
				$tmpurl .= "/gp/product/".$item->order_source_itemid;
		
				$product_name_url = $tmpurl;
				break;
			default:
				$product_name_url = $item->product_url;
		}
		
		return $product_name_url;
	}
	
	//保存常用卖家账号的组合
	public static function setPlatformCommonCombination($params){
		$result = array('error'=>false, 'msg'=>'');
		
		$uid = \Yii::$app->user->id;
		
		$tmp_erp_platform_commona = array();
		$tmp_erp_platform_common_json = RedisHelper::RedisGet('Erp_PlatformCommonCombination', $uid);
		if(!empty($tmp_erp_platform_common_json)){
			$tmp_erp_platform_commona = json_decode($tmp_erp_platform_common_json, true);
		}
		
		if(isset($tmp_erp_platform_commona[$params['type']][$params['platform']][$params['com_name']])){
			$result['error'] = true;
			$result['msg'] = '组合名称重复，请使用其它名称';
			return $result;
		}
		
		$tmp_erp_platform_commona[$params['type']][$params['platform']][$params['com_name']] = $params['platform_code_selected'];
		
		
		RedisHelper::RedisSet('Erp_PlatformCommonCombination', $uid, json_encode($tmp_erp_platform_commona));

		$result['msg'] = '保存成功';
		return $result;
	}
	
	//获取常用卖家账号的组合
	public static function getPlatformCommonCombination($params, $uid = false){
		if($uid == false){
			$uid = \Yii::$app->user->id;
		}
		
		$tmp_erp_platform_commona = array();
		$tmp_erp_platform_common_json = RedisHelper::RedisGet('Erp_PlatformCommonCombination', $uid);
		if(!empty($tmp_erp_platform_common_json)){
			$tmp_erp_platform_commona = json_decode($tmp_erp_platform_common_json, true);
		}
		
		$pcCombination = array();
		
		if(isset($tmp_erp_platform_commona[$params['type']][$params['platform']])){
			foreach ($tmp_erp_platform_commona[$params['type']][$params['platform']] as $tmp_key => $tmp_val){
				$pcCombination[$tmp_key] = $tmp_val;
			}
		}
		
		if(isset($params['com_name'])){
			if(isset($pcCombination[$params['com_name']])){
				return $pcCombination[$params['com_name']];
			}else{
				return array();
			}
		}
		
		return $pcCombination;
	}
	
	//清除常用卖家账号的组合
	public static function removePlatformCommonCombination($params){
		$result = array('error'=>false, 'msg'=>'');
	
		$uid = \Yii::$app->user->id;
	
		$tmp_erp_platform_commona = array();
		$tmp_erp_platform_common_json = RedisHelper::RedisGet('Erp_PlatformCommonCombination', $uid);
		if(!empty($tmp_erp_platform_common_json)){
			$tmp_erp_platform_commona = json_decode($tmp_erp_platform_common_json, true);
		}
	
		if(isset($tmp_erp_platform_commona[$params['type']][$params['platform']][$params['com_name']])){
			unset($tmp_erp_platform_commona[$params['type']][$params['platform']][$params['com_name']]);
			
			RedisHelper::RedisSet('Erp_PlatformCommonCombination', $uid, json_encode($tmp_erp_platform_commona));
			
			$result['msg'] = '删除成功';
			return $result;
		}else{
			$result['error'] = true;
			$result['msg'] = '该组合已不存在';
			return $result;
		}
	}
	
	//获取用户是否有设置显示OMS 所有的List界面显示特殊的操作
	public static function getOmsAllOrderListOperation($puid = false){
		if(empty($puid)){
			$puid = \Yii::$app->user->identity->getParentUid();
		}

		//$tmp_result	1表示显示特殊的操作，2表示不显示
		$tmp_result = ConfigHelper::getConfig('order/oms-all-list-operation');
		
		if(empty($tmp_result)){
			if($puid > 1000){
				$tmp_result = 2;
			}else{
				$tmp_result = 1;
			}
			
			ConfigHelper::setConfig('order/oms-all-list-operation', $tmp_result);
		}
		
		return $tmp_result;
	}
	
	//是否返回显示额外的操作选项
	public static function getIsShowMenuAllOtherOperation(){
		$puid = \Yii::$app->subdb->getCurrentPuid();
		
		$getIsShowMenuAllOtherOperation = ConfigHelper::getGlobalConfig('Order/isShowMenuAllOtherOperation_'.$puid, "NO_CACHE");
		
		if(empty($getIsShowMenuAllOtherOperation)){
			if($puid < 16270){
				$getIsShowMenuAllOtherOperation = 'Y';
			}else{
				$getIsShowMenuAllOtherOperation = 'N';
			}
			
			ConfigHelper::setGlobalConfig('Order/isShowMenuAllOtherOperation_'.$puid, $getIsShowMenuAllOtherOperation);
		}
		
		if($getIsShowMenuAllOtherOperation == 'Y'){
			return true;
		}else{
			return false;
		}
	}
	
	//是否返回显示额外的操作选项
	public static function getIsShowAvailableStock(){
		$puid = \Yii::$app->subdb->getCurrentPuid();
	
		$getIsShowAvailableStock = ConfigHelper::getGlobalConfig('Order/isShowAvailablestock_'.$puid, "NO_CACHE");
		
		if(empty($getIsShowAvailableStock)){
			$getIsShowAvailableStock = 'N';
			ConfigHelper::setGlobalConfig('Order/isShowAvailablestock_'.$puid, $getIsShowAvailableStock);
		}
		
		if($getIsShowAvailableStock == 'Y'){
			return true;
		}else{
			return false;
		}
	}
	
}
?>