<?php
use yii\helpers\Url;
use yii\helpers\Html;
use eagle\modules\order\helpers\OrderListV3Helper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\order\helpers\OrderFrontHelper;

// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/carrier/customprint/jquery-ui.min.js", ['depends' => ['yii\jui\JuiAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/purchase/purchase_link_list.js?v=1.0", ['depends' => ['yii\web\JqueryAsset']]);

$current_exception_status = empty($current_exception_status) ? '' : $current_exception_status;
//合并订单显示
$isMergeOrder = 0;
if (($current_order_status == 200) && ($current_exception_status == 223)){
	$isMergeOrder = 1;
	$this->registerJs("orderCommonV3.showMergeRow();" , \yii\web\View::POS_READY);
}

$is_purchase = \eagle\modules\permission\apihelpers\UserApiHelper::checkModulePermission('purchase');

?>

<style>
.table_list_v3 {
	width: 100%;
    max-width: 100%;
    margin-bottom: 20px;
	border-collapse: collapse;
    border-spacing: 0;
	line-height: 22px;
}

.table_list_v3 > tbody > tr > th {
	padding: 7px;
	background-color: #d9effc;
	font: bold 13px SimSun,Arial;
	text-align: center;
	font-weight: bold;
	color: #374655;
	white-space: nowrap;
}

.table_list_v3 tr td {
    text-align: left;
    vertical-align: top;
}

.table_list_v3 > tbody > tr:hover{
	background-color: #f4f9fc;
}

.ck_0_v3, .ck_v3{
	float: left;
}

.table_list_v3_tr{
	background-color: #f4f9fc;
/* 	background-color: #d9effc; */
	border: 1px solid #ccc;
}

.table_list_v3_tr > td{
	padding: 7px;
}

.list_order_num{
	display: inline-block;
	text-align: left;
	margin-left: 5px;
}

.label_memo_custom{
	background-color: #009926;
}

.label_o_custom{
	background-color: orange;
}

.label_custom{
	font-size:13px;
	font-weight:100;
	padding:0 5px;
/* 	width: 20px; */
    height: 20px;
	line-height: 20px;
	display: inline-block;
	color: #fff;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: .25em;
	cursor: pointer;
}

.list_order_span_7{
	margin-left:7px;
}

.list_order_num_r, .margin_r10{
	margin-right: 10px;
}

.table_list_v3_tr_items{
/* 	background-color: #f4f9fc; */
	border: 1px solid #ccc;
}

.table_list_v3_tr_items > td{
	padding: 5px;
	border: 1px solid #ccc;
}

span.badge.badge_orange{
	background:rgb(255,153,0);
/* 	border-radius:8px; */
 	color:#fff;
}

span.badge.badge_grey{
	background:#999;
	color:#fff;
}

.span_simsun_100{
	font: 100 13px SimSun,Arial;
}

.p_omit_200{
	width:200px;
	overflow:hidden;
	text-overflow:ellipsis;
}

.div_alert_200{
	max-width: 200px;
    word-break: break-all;
	padding: 5px;
	margin-bottom: 0px;
}

.span_icon_gray{
	color: #999;
}

.span_icon_green{
	color: #009f3c;
}

.cd-oms-weird-status-wfs-v3{
	background: url(/images/cdiscount/cdiscount_icon.png) no-repeat -1px -1627px;
    background-size: 100%;
    float: left;
    width: 18px;
    height: 18px;
	margin-right:5px;
}

.cd_fbc_inco_v3{
	width:15px;
	height:15px;
	background:url("/images/cdiscount/clogpicto.jpg") no-repeat;
	display: block;
    background-size: 15px;
	float:left;
	margin:2px 5px 0 0;
}

.div_newline_v3 , .p_newline_v3{
	word-break: break-all;
	word-wrap: break-word;
}

.tr_td_border_bottom{
	border:1px solid #d1d1d1;
	height:7px;
}

.div_item_momo{
	width:200px;
	overflow:hidden;
	text-overflow:ellipsis;
}

.button_pm_item{
	padding: 0px 4px;
	margin-left:5px;
}

.span_be_orange{
	color: #ff6000;
}

.order-param-group , .prod-param-group{
	width: 280px;
	float: left;
	text-align: right;
	display: block;
	margin-right: 10px;
}

.used_stock_info{
	color: #999999;
	margin-left: 5px;
	font-size: 10px;
}
.div_add_tag{
	width: 700px;
}

</style>
<table class="table_list_v3" style="font-size:12px;">
<tr style='border: 1px solid #ccc;' >
	<th style='min-width:180px;'><input id="ck_all_v3" class="ck_0_v3" type="checkbox" onchange="orderCommonV3.selected_switch()"> 商品信息</th>
	<th style='min-width:110px;'>收件人
		<?php if(isset($countrys)){
			echo Html::dropDownList('country',@$_REQUEST['country'],$countrys,['prompt'=>'国家','style'=>'width:50px','onchange'=>"orderCommonV3.dosearch('country',$(this).val());"]);
		}else{
			echo '「国家」';
		} ?>
	</th> 
	<th style='min-width:160px;'>时间</th>
	<th style='min-width:120px;'>
		<?php
			if(isset($carriers)){
				echo Html::dropDownList('shipmethod',@$_REQUEST['shipmethod'],$carriers,['prompt'=>'物流方式','style'=>'width:90px','onchange'=>"orderCommonV3.dosearch('shipmethod',$(this).val());"]);
			}else{
				echo '物流方式';
			}
		?>
	</th>
	<?php
	//对应平台的Qitp说明
	$platform_qtipkey = '';
	$platform_operate_qtipkey = '';
	
	switch ($platform_type){
		case 'ebay':
			$platform_qtipkey = '<span qtipkey="oms_order_platform_status_ebay"></span>';
			$platform_operate_qtipkey = '<span qtipkey="oms_order_action_ebay"></span>';
			break;
		case 'cdiscount':
			$platform_qtipkey = '<span qtipkey="oms_order_platform_status_cdiscount"></span>';
			$platform_operate_qtipkey = '<span qtipkey="oms_order_action_cdiscount"></span>';
			break;
		case 'aliexpress':
			$platform_qtipkey = '<span qtipkey="oms_order_platform_status_aliexpress"></span>';
			$platform_operate_qtipkey = '<span qtipkey="oms_order_action_aliexpress"></span>';
			break;
		case 'amazon':
			$platform_operate_qtipkey = '<span qtipkey="oms_order_action_amazon"></span>';
			break;
		case 'customized':
			break;
		case 'bonanza':
			$platform_qtipkey = '<span qtipkey="oms_bononza_status"></span>';
			break;
		case 'dhgate':
			$platform_operate_qtipkey = '<span qtipkey="oms_order_action_dhgate"></span>';
			break;
		case 'priceminister':
			$platform_qtipkey = '<span qtipkey="oms_order_platform_status_priceminister"></span>';
			$platform_operate_qtipkey = '<span qtipkey="oms_order_action_priceminister"></span>';
			break;
		case 'rumall':
			$platform_qtipkey = '<span qtipkey="runmall-platform-status"></span>';
			break;
		case 'wish':
			$platform_qtipkey = '<span qtipkey="oms_order_platform_status_wish"></span>';
			break;;
		default:
			$platform_qtipkey = '';
			break;
	}
	?>
	<th style='min-width:130px;'>小老板状态<span qtipkey="oms_order_lb_status_description"></span>/平台状态<?=empty($platform_qtipkey) ? '' : $platform_qtipkey ?></th>
	<th style='min-width:140px;'>物流状态<span qtipkey="oms_order_carrier_status_description"></span></th>
	<th style='min-width:74px;width:74px;max-width:74px;' >操作<?=empty($platform_operate_qtipkey) ? '' : $platform_operate_qtipkey ?></th>
</tr>

<tr id="showSelChboxNum" style="display:none;"><td style="height: 30px;border: 1px solid #ccc;background-color: #FFFFCD;text-align: left;margin-left: 3px;vertical-align:middle;padding-left: 30px;" colspan="7"><span style="padding-left: 30px;box-sizing: border-box;width: 100%">已选中 条数据</span></td></tr>

<?php
	$divTagHtml = '';
	$div_event_html = '';
	//先统一获取SKU信息
	$skuListInfo = OrderListV3Helper::getOrderSkuListInfo($models);
	
	//解释order_models 
	$orderInfos = OrderListV3Helper::getOrderListInfoByOrderModels($models, $warehouses, $non17Track, $current_order_status, $skuListInfo, empty($current_exception_status) ? '' : $current_exception_status, empty($selleruserids) ? '' : $selleruserids,true);
?>

<?php if (count($orderInfos)):foreach ($orderInfos as $order):?>
<?php
	$divTagHtml .= '<div id="div_tag_'.$order['order_id'].'"  name="div_add_tag" class="div_space_toggle div_add_tag"></div>';
	$div_event_html .= $order['order_div_event_html'];
?>
<tr class='table_list_v3_tr' data-source-order-id="<?=$order['order_source_order_id']?>" <?= empty($order['group_order_md5'])?"":"merge_row_tag_v3='".$order['group_order_md5']."'"; ?>>
	<td colspan="2">
		<input type="checkbox" class="ck_v3" name="order_id[]" value="<?=$order['order_id']?>" onchange="orderCommonV3.selected_switch()">
		
		<div class='list_order_num'><?=$order['platform_order_no'].' <span title="小老板订单号" class="span_simsun_100">「'.((int)$order['order_id']).'」</span>' ?></div>
		
		<?php 
		if(!empty($order['order_related_icon'])){
			echo '<div class="list_order_num">'.$order['order_related_icon'].'</div>';
		}
		?>
		
		<div class='list_order_num'><?=$order['TagStr'] ?></div>
		
		
		<?php
		if(($order['exception_status'] > 0) && ($order['exception_status'] != '201')){
		?>
		<div class='list_order_num'>
			<?php
				if(in_array($order['exception_status'], array('210','223'))){
					echo '<span class="label_custom label_o_custom" title="'.OdOrder::$exceptionstatus[$order['exception_status']].'">'.($order['exception_status'] == '210' ? 'SKU' : '合并').'</span>';
				}else{
					echo '<div '.(($order['exception_status'] == '299') ? 'style="background-position: -199px -10px;"' : '').' title="'.OdOrder::$exceptionstatus[$order['exception_status']].'" class="exception_'.$order['exception_status'].'"></div>';
				}
			?>
		</div>
		<?php
		}
		?>
		
		<?php
		if(strlen($order['user_message']) > 0){
		?>
			<div class='list_order_num' style='margin: 0;'>
			 <div title="<?=OdOrder::$exceptionstatus[OdOrder::EXCEP_HASMESSAGE] ?>" class="exception_<?=OdOrder::EXCEP_HASMESSAGE ?>"></div>
			</div>
		<?php 
		}
		?>
		
		<?php
		if($order['order_capture'] == 'Y'){
		?>
		<div class='list_order_num'>
			<span class='label_custom label_o_custom' style='cursor: auto;'><?=(($order['reorder_type']=='after_shipment') ? '补' : '手工') ?></span>
		</div>
		<?php
		}
		?>
		<?php 
		/* wish 5月1号实行的确认妥投政策
		 * 1.2017-05-01以后的订单
		 * 2.平台为wish
		 * 3.产品与运费总价格在10美金以上
		 * 4.配送到美国 ， 法国，德国，英国
		 */
		if ($order['order_source'] =='wish' && $order['order_source_create_time'] > 1493568000 && $order['grand_total']>=10 && in_array($order['consignee_country_code'],['US','FR','DE' , 'GB' , 'UK']) && $order['order_capture'] =='N'){
			echo "
		<div class='list_order_num'>
			<span class='label_custom label_o_custom' style='cursor: auto;'>需要确认妥投</span>
		</div>
		";
		}
		
		if($order['order_relation'] == 'fs' || $order['order_relation'] == 'ss'){
		?>
				<div class='list_order_num'>
					<span class='label_custom label_o_custom' style='cursor: auto;'>拆</span>
				</div>
		<?php 
		}
		?>
		<div class='list_order_num' <?=(empty($order['desc']) ? 'style="display:none;"' : '') ?> >
			<span class="label_custom label_memo_custom " data-container="body" data-toggle="popover" data-placement="top" data-content="" data-original-title="" title="">备</span>
		</div>
	</td>
	<td colspan='<?=($isMergeOrder == 1) ? '4' : '5'; ?>' >
		<div class='list_order_num'>
			<?=$order['amountInfoHtml'] ?>
			<?=$order['profits_info'] ?>
		</div>
<!--	</td>-->
<!--	<td colspan="2" style='text-align: right;'> -->
		
		<?php
		if($order['order_source'] == 'lazada'){
		?>
		<div class="limingWarp list_order_num list_order_num_r" style="position:relative;display: inline;text-align: right;float: right;">
			<a href="javascript:" class="limingcentUrlpic" custom_prompt="复制:卖家账号" custom_width="100" custom_content="<?=$order['selleruserid'] ?>"><?=$order['order_source'].''.(empty($order['order_site_id']) ? '' : ' '.$order['order_site_id']).': '.$order['order_shop_name'] ?></a>
		</div>
		<?php
		}else{
		?>
		<div style='text-align: right;float: right;' class='list_order_num list_order_num_r'><?=$order['order_source'].''.(empty($order['order_site_id']) ? '' : ' '.$order['order_site_id']).': '.$order['order_shop_name'] ?></div>
		<?php
		}
		?>
	</td>
	<?php 
// 		echo ($isMergeOrder == 1) ? '<td></td>' : '';
	?>
</tr>

<tr class='table_list_v3_tr_items' <?= empty($order['group_order_md5'])?"":"merge_row_tag_v3='".$order['group_order_md5']."'"; ?>>
	<td>
		<table style='width: 100%;'>
		<?php 
			$mergeHtml = '';
			if(count($order['items']) > 0){
				foreach ($order['items'] as $key => $item){
					if ($order['order_relation'] == 'sm'){
						//合并订单显示 合并前的订单号
						if(in_array($order['order_source'],['ebay'])){
							if (!empty($item['order_source_srn'])){
								$mergeHtml.=  '<br><font color="#8b8b8b">合并前SRN:</font><b>'.$item['order_source_srn'].'</b>';
							}
						}else{
							if (!empty($item['order_source_order_id'])){
								$mergeHtml.=  '<br><font color="#8b8b8b">合并前订单号:</font><b>'.$item['order_source_order_id'].'</b>';
							}
						}
						
					}
		?>
		<tr>
			<td style='width:68px;text-align:center;'>
				<div style='border: 1px solid #cccccc;width: 62px;height: 62px;'>
					<img class="prod_img" style='max-width:100%; max-height:100%; width: auto;height: auto;' src="<?=$item['photo_primary_url'] ?>" data-toggle="popover" data-content="<img width='350px' src='<?=str_replace('.jpg_50x50','',$item['big_photo_primary_url'])?>'>" data-html="true" data-trigger="hover">
				</div>
			</td>
			<td>
				<div>
					<p class='p_newline_v3'>
						<?=$item['sku'].' x '.'<span class="badge '.($item['quantity'] > 1 ? 'badge_orange' : 'badge_grey').'" >'.$item['quantity'].'</span>' ?>
						<?php
							if($is_purchase == true){
								if($item['is_product'] == false){
									echo "<a href='/catalog/product/list' target='_blank'><span class='glyphicon glyphicon-shopping-cart' style='color:rgb(139, 139, 139);cursor:pointer;margin-left:5px;' title='商品还没在商品模块创建，点击前往创建商品'></span></a>";
								}else{
									if(empty($item['purchase_link'])){
										echo "<a href='/catalog/product/list?search_type=&txt_search=".$item['sku']."' target='_blank'><span class='glyphicon glyphicon-shopping-cart' style='color:rgb(139, 139, 139);cursor:pointer;margin-left:5px;' title='商品还没在商品模块设置采购链接，点击前往商品模块设置'></span></a>";
									}else{
										echo "<a href='".$item['purchase_link']."' target='_blank' class='purchase_link_list_show' purchase_link_json='".$item['purchase_link_list']."'><span class='glyphicon glyphicon-shopping-cart' title='商品已于商品模块设置了采购链接，点击打开该链接' style='cursor:pointer;color:#2ecc71;margin-left:5px;'></span></a>";
									}
								}
							}
						?>
						
						<?= empty($item['is_warehouse_send']) ? '' : '<span class="badge badge_grey">尖货</span>' ?>
					</p>
					<?php
						if($item['show_root_sku'] != ''){
							echo '<p class="p_newline_v3" style="color:#33a3dd;"><span class="span_simsun_100" aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="配对SKU" title="">'.$item['show_root_sku'].'</span>'.(($item['used_stock_info'] != '') ? $item['used_stock_info'] : '').'</p>';
						}
					
						if(!empty($item['platform_source_item_id'])){
							echo '<p>'.$item['platform_source_item_id'].'</p>';
						}
						
						if(!empty($item['platform_item_html'])){
							echo $item['platform_item_html'];
						}
					?>
					
					<p><?=$order['currency'].' '.$item['price'] ?> <?=(empty($item['platform_item_html2']) ? '' : $item['platform_item_html2']) ?></p>
					<p class='p_omit_200' data-container="body" data-toggle="popover" data-placement="top" data-content='<?php echo (str_replace("'","&apos;",$item['product_name'])); ?>' data-original-title="" title="" >
						<nobr>
							<a href='<?=$item['product_name_url'] ?>' target="_blank" >
								<span ><?php echo $item['product_name']; ?></span>
							</a>
						</nobr>
					</p>
					<?php
					if(count($item['product_attributes_arr']) > 0){
					?>
 					<p>
 					<?php
 					$attr_count = 0;
					foreach ($item['product_attributes_arr'] as $tmp_product_attributes){
						if($order['order_source'] == 'shopee' && $attr_count > 0 && $attr_count % 4 == 0){
							echo '<br>';
						}
						$attr_count++;
						echo '<span class="label label-warning">'.$tmp_product_attributes.'</span>';
					}
 					?>
					</p>
					<?php
					}
					?>
					--
				</div>
			</td>
		</tr>
		<?php
				}
			}
		?>
		</table>
	</td>
	<td >
		<div class='div_newline_v3'>
		<p>
		<span><?=$order['consignee'] ?></span>
		<span class="span_simsun_100" aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="<?=$order['country_en'] ?>" title=""><?='「'.$order['country_zh'].'」' ?></span>
		<span><?=$order['consignee_country_code'] ?></span>
		</p>
		
		<?php
		if(!empty($order['buyer_user_account'])){
			echo '<p><span aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="买家账号" title="">'.$order['buyer_user_account'].'</span></p>';
		}
		echo $mergeHtml;
		?>
		
		</div>
	</td>
	<td>
		<?php
			$tmp_timeLeft = empty($order['timeLeft']) ? '' : '<span id="timeleft_'.$order['order_id'].'" class="fulfill_timeleft" data-order-id="'.$order['order_id'].'" data-time="'.($order['timeLeft']-time()).'"></span>';
		
			$tmp_source_create_date = '下单：'.($order['order_source_create_time'] > 0 ? date('Y-m-d H:i',$order['order_source_create_time']) : '');
			$tmp_paid_time = '付款：'.($order['paid_time'] > 0 ? date('Y-m-d H:i',$order['paid_time']) : '');
			
			$tmp_operate_time = '';
// 			if($order['order_source'] == 'priceminister'){
// 				$tmp_operate_time = '<br><span aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="订单最后操作日期" title="">操作：'.date('Y-m-d H:i',$order['update_time']).'</span>';
// 			}
			
			$tmp_order_ship_time = '';
			$tmp_complete_ship_time = '';
			if($order['is_no_delivery'] == false){
				$tmp_order_ship_time = '<br>提交：'.($order['order_ship_time'] != null ? date('Y-m-d H:i',strtotime($order['order_ship_time'])) : '');
				$tmp_complete_ship_time = '<br>发货：'.($order['complete_ship_time'] > 0 ? date('Y-m-d H:i',$order['complete_ship_time']) : '');
			}
			
			if(!empty($tmp_timeLeft)){
				echo '<p>'.$tmp_timeLeft.'</p>';
			}
			
			echo $tmp_source_create_date.'<br>'.$tmp_paid_time.$tmp_operate_time.$tmp_order_ship_time.$tmp_complete_ship_time;
		?>
	</td>
	<td>
		<div class='div_newline_v3'>
		<?php
			if(!empty($order['buyer_choose_shipping_method'])){
				echo '<p>'.'客选物流：'.$order['buyer_choose_shipping_method'].'</p>';
			}
			
			if($order['is_no_delivery'] == false){
				if(!empty($order['warehouse_name'])){
					echo '<p>'.$order['warehouse_name'].'</p>';
				}
				
				if(($order['order_status'] == OdOrder::STATUS_PAY) && ((empty($order['default_carrier_code'])) || (empty($order['default_shipping_method_code'])))){
					$tmp_transport_service_show = false;
				}else{
					$tmp_transport_service_show = true;
				}
				
				echo '<p '.(($tmp_transport_service_show == false) ? '' : 'style="display:none;"').' class="p_transport_service_unset">['.'<span style="color:red;">运输服务未选择</span>'.']</p>';
				echo '<p '.(($tmp_transport_service_show == false) ? '' : 'style="display:none;"').' class="p_transport_service_unset">[<a onclick="orderCommonV3.doactionone(\'changeWHSM\',\''.$order['order_id'].'\',\''.$order['order_source'].'\')" >请选择运输服务</a>]</p>';
				echo '<p '.(($tmp_transport_service_show == true) ? '' : 'style="display:none;"').' class="p_transport_service_set">运输服务：'.(isset($carriers[$order['default_shipping_method_code']]) ? $carriers[$order['default_shipping_method_code']] : '').' '.((empty($order['to_deal_with'])) ? '' : $order['to_deal_with']).'</p>';
				
				if ((!empty($order['carrier_error'])) && ($order['order_status'] == OdOrder::STATUS_PAY)){
					$tmp_send_addres = '';
	
					if (stripos('123'.$order['carrier_error'],'地址信息没有设置好，请到相关的货代设置地址信息')){
						if (!empty($order['default_carrier_code'])){
							$tmp_send_addres = '<br><a target="_blank" href="/configuration/carrierconfig/index?carrier_code='.$order['default_carrier_code'].'">'.TranslateHelper::t('设置发货地址').'</a>';
						}
					}
	
					echo '<div class="div_alert_200 alert alert-danger" >'.$order['carrier_error'].$tmp_send_addres.'</div>';
				}
				
				if(in_array($order['order_item_is_empty'], array(2, 3))){
					echo '<p class="p_declaration_info">['.'<span style="color:red;">'.(($order['order_item_is_empty'] == 2) ? '报关信息不全' : '全局报关信息').'</span>'.']</p>';
					echo '<p class="p_declaration_info">[<a onclick="orderCommonV3.doactionone(\'changeItemDeclarationInfo\',\''.$order['order_id'].'\',\''.$order['order_source'].'\')" >请编辑报关信息</a>]</p>';
				}
			}
		?>
		</div>
	</td>
	<td>
		<?php
			echo '<p class="xlb_status" ><b aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="小老板状态" title="">'.(isset(OdOrder::$status[$order['order_status']]) ? OdOrder::$status[$order['order_status']] : $order['order_status'] ).'</b></p>';
			
			if(!empty($order['print_icon'])){
				echo '<p>'.$order['print_icon'].'</p>';
			}
			
			if(is_array($order['platform_state_icon'])){
				if(count($order['platform_state_icon']) > 0){
					if(isset($order['platform_state_icon'][0])){
						foreach ($order['platform_state_icon'] as $tmp_platform_state_icon){
							echo '<p ><b aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="平台状态'.$tmp_platform_state_icon['title'].'" title="">'.$tmp_platform_state_icon['status'].'</b></p>';
						}
					}else{
						echo '<p>';
						foreach ($order['platform_state_icon'] as $tmp_platform_state_icon){
							echo '<span class="'.$tmp_platform_state_icon['class'].'" title="'.$tmp_platform_state_icon['title'].'"></span>';
						}
						echo '</p>';
					}
				}
			}else{
				if(!empty($order['platform_state_icon'])){
					echo '<p ><b aria-hidden="true" data-toggle="tooltip" data-placement="top" data-html="true" data-original-title="平台状态" title="">'.$order['platform_state_icon'].'</b></p>';
				}
			}
			
			if(!empty($order['user_message'])){
				echo '<p class="p_omit_200" data-container="body" data-toggle="popover_delay" data-placement="top" data-content=\''.(str_replace("'","&apos;",$order['user_message'])).'\' data-original-title="" title=""><nobr>付款备注：<a><span class="span_be_orange" >'.$order['user_message'].'</span></a></nobr></p>';
			}
			
			if(!empty($order['last_message'])){
				echo '<p class="p_omit_200" data-container="body" data-toggle="popover_delay" data-placement="top" data-html="true" data-content=\''.(str_replace("'","&apos;",$order['last_message'])).'\' data-original-title="" title=""><nobr>买家留言：<a onclick="ShowDetailMessage(\''.$order['selleruserid'].'\',\''.$order['source_buyer_user_id'].'\',\''.$order['order_source'].'\',\'\',\'0\',\'\')" >'.strip_tags($order['last_message'], '<br>').'</a></nobr></p>';
			}
			
			if(!empty($order['seller_comment']['text'])){
				echo '<p class="p_omit_200" data-container="body" data-toggle="popover_delay" data-placement="top" data-html="true" data-content=\''.(str_replace("'","&apos;",$order['seller_comment']['text'])).'\' data-original-title="" title=""><nobr>买家评价：<a title="'.$order['seller_comment']['text'].'">'.$order['seller_comment']['type'].' '.$order['seller_comment']['text'].'</a></nobr></p>';
			}
			
			echo '<p '.(!empty($order['desc']) ? '' : 'style="display:none;"').' class="p_newline_v3 div_item_momo">订单备注：'.$order['desc'].'</p>';
		?>
	</td>
	<td >
		<?php 
			if($order['order_status'] == OdOrder::STATUS_WAITSEND){
				if(!in_array($order['carrier_step'], array(3,4))){
					echo '<p>'.CarrierHelper::$carrier_step[$order['carrier_step']].'</p>';
				}
			}
		?>
		<?php
			if(count($order['order_ship_info']) > 0){
				foreach ($order['order_ship_info'] as $tmp_order_ship_one){
					if(isset($tmp_order_ship_one['errors'])){
						echo '<p style="color:red;max-width:120px;word-break: break-all;word-wrap: break-word;">'.$tmp_order_ship_one['errors'].'</p>';
					}

					if(isset($tmp_order_ship_one['status'])){
						echo '<p>'.$tmp_order_ship_one['status'].'</p>';
					}
					
					if(isset($tmp_order_ship_one['carrier_type'])){
						echo '<p>'.$tmp_order_ship_one['carrier_type'].'</p>';
					}
					
					if(isset($tmp_order_ship_one['tracking_url'])){
						echo '<p style="word-break: break-all;word-wrap: break-word;">'.$tmp_order_ship_one['tracking_url'].(isset($tmp_order_ship_one['ignored']) ? $tmp_order_ship_one['ignored'] : '').'</p>';
						
// 						echo '<div style="word-break: break-all;word-wrap: break-word;">'.$tmp_order_ship_one['tracking_url'].(isset($tmp_order_ship_one['ignored']) ? $tmp_order_ship_one['ignored'] : '').'</div>';
					}
					
					if(isset($tmp_order_ship_one['is_ignored'])){
						echo '<p>'.$tmp_order_ship_one['is_ignored'].'</p>';
					}
				}
			}
		?>
		<?php
		if ( !empty($order['seller_weight']) && (int)$order['seller_weight']!==0 ){
			echo '<p>'."称重重量：".(int)$order['seller_weight']." g".'</p>';
		}
		
		?>
	
	</td>
	<?php
	if($isMergeOrder == 1){
// 		echo '<td></td>';
	}else{
	?>
	<td>
		<?php
			echo $order['edit_operation']['edit'];
			
			echo Html::dropDownList('','', $order['edit_operation']['do_operation_list_one'], ['onchange'=>"orderCommonV3.doactionone2(this,'".$order['order_id']."','".$order['order_source']."');",'class'=>'eagle-form-control','style'=>'width:70px;']);
			
			echo '<a href="javascript:void(0)" onclick="orderCommonV3.doactionone(\'addMemo\',\''.$order['order_id'].'\',\''.$order['order_source'].'\');" ><font color="00bb9b">备注</font></a>';
		?>
	</td>
	<?php } ?>
</tr>

<tr style="background-color: #d9d9d9;<?= empty($order['group_order_md5'])?"":"display:none;"; ?>"  >
	<td colspan="7" class="row tr_td_border_bottom" id="dataline-<?=$order['order_id'];?>" ></td>
</tr>

<?php endforeach;endif; ?>
</table>

<div style='display: none;'>
	<?php echo $divTagHtml; ?>
	<?php echo $div_event_html; ?>
</div>

<?php
$this->registerJs("$('[data-toggle=\"popover\"]').popover({trigger: 'hover'});" , \yii\web\View::POS_READY);
// $this->registerJs("$('[data-toggle=\"tooltip\"]').tooltip();" , \yii\web\View::POS_READY);

	$this->registerJs("$('[data-toggle=\"popover_delay\"]').popover({trigger: 'manual'}).on(\"mouseenter\", function () {
	                    var _this = this;
	                    $(this).popover(\"show\");
	                    $(this).siblings(\".popover\").on(\"mouseleave\", function () {
	                        $(_this).popover('hide');
	                    });
	                }).on(\"mouseleave\", function () {
	                    var _this = this;
	                    setTimeout(function () {
	                        if (!$(\".popover:hover\").length) {
	                           $(_this).popover(\"hide\");
	                        }
	                    }, 2000);
	                });" , \yii\web\View::POS_READY);

	$this->registerJs("$('[data-toggle=\"tooltip\"]').tooltip();" , \yii\web\View::POS_READY);
?>

