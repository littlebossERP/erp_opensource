<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\order\models\OrderRelation;
?>

<style>
.table_list_v3_tr{
	background-color: #f4f9fc;
	border: 1px solid #ccc;
}

.table_list_v3_tr > td{
	padding: 7px;
}

.table_list_v3_tr_items{
	border: 1px solid #ccc;
}

.table_list_v3_tr_items > td{
	padding: 5px;
	border: 1px solid #ccc;
}

.tr_td_border_bottom{
	border:1px solid #d1d1d1;
	height:7px;
}

.ui-autocomplete{
	z-index:9999;
	z-index: 9999 !important;
	overflow-y: scroll;
	max-height: 320px;
}
</style>

<?php
if(isset($error_arr)){
	echo '<span style="font-size: 24px;">'.'失败'.':'.$error_arr['error'].'</span>';

	echo Html::button('关闭',['class'=>'btn btn-success colse_btn_signshipped','style'=>'margin-left:20px;']);
}else{
?>

<form id='sigshipped_new_form'>
<table class="table_list_v3">

<?php if (count($orders)):foreach ($orders as $order):?>

<?php
$odship=OdOrderShipped::find()->where(['order_id'=>$order->order_id])->orderBy('id DESC')->one();
//如果合并订单的原始订单没有发货信息，即读取合并订单发货信息
if ($odship==null){
	$smOrderRel = OrderRelation::findOne(['father_orderid'=>$order->order_id , 'type'=>'merge']);
	if(!empty($smOrderRel)){
		$odship=OdOrderShipped::find()->where(['order_id'=>$smOrderRel->son_orderid])->orderBy('id DESC')->one();
	}
}

if ($odship==null){
	$odship = new OdOrderShipped();
	
	//假如之前没有通知过平台发货，但是已经选择了运输服务，那么需要直接读取之前的值来默认填上
	if((int)$order->default_shipping_method_code > 0){
		$service = \eagle\modules\carrier\models\SysShippingService::find()->where(['id'=>$order->default_shipping_method_code,'is_used'=>1,'is_del'=>0])->one();

		if($service != null){
			$odship->tracking_link = $service->web;
			$odship->shipping_method_code = isset($service->service_code[$order->order_source]) ? $service->service_code[$order->order_source] : '';
		}
	}
}
?>

<tr class='table_list_v3_tr'>
	<td colspan="5">
		小老板订单号:
			<b><?=$order->order_id?><?=Html::input('hidden','order_id[]',$order->order_id)?></b>&nbsp;
  			<?php if ($order->order_source == 'ebay'):?>
  				<?= ucfirst($order->order_source)?> SRN:<b><?=$order->order_source_srn?></b>
  			<?php else:?>
  				<?= ucfirst($order->order_source)?>订单号:<b><?=$order->order_source_order_id?></b>
  			<?php endif;?>
  			
  			&nbsp;收件人:<?=$order->source_buyer_user_id?>&nbsp;
	  		
	  		<?php if (count($order->queueships)):?>
				<span style="background-color:#fff8d9"><font color="red">!</font>该订单在标记发货队列中已处理或未处理,如果不希望重复标记,您可以取消该订单</span>
			<?php endif;?>
	</td>
</tr>

<tr class='table_list_v3_tr_items'>
	<td style="line-height: 40px;display:none;">收件人<?=$order->source_buyer_user_id?></td>
	
	<td>
  		运单号
	  	<?php if(in_array($order->order_source, ['cdiscount']) && !empty($odship->tracking_number)):?>
  			<?=Html::input('',"tracknum[$order->order_id]",@$odship->tracking_number,['size'=>25,'style'=>'width:auto;cursor:not-allowed;background-color:#eee;display:inline-block;','readonly'=>'readonly','class'=>'form-control'])?>
  		<?php else:?>
  			<?=Html::input('',"tracknum[$order->order_id]",@$odship->tracking_number,['size'=>25,'style'=>'width:auto;display:inline-block;','class'=>'form-control'])?>
  		<?php endif;?>
  	</td>
  	
  	<td style='<?=($allShipcodeMapping[$order->order_source]['web_url_tyep'] != 1 ? '' : 'display:none;') ?>'>
  		查询网址<?=Html::input('',"trackurl[$order->order_id]",strlen($odship->tracking_link)?$odship->tracking_link:'',['size'=>45,'style'=>'width:auto;display:inline-block;','class'=>'form-control'])?>
  	</td>
  	
  	<td>运输服务
  			<?php 
  			$othermethodDisplay='display:block';
  			if (strlen($odship->shipping_method_code)>0){
				$method = $odship->shipping_method_code;
			}else{
				if (!empty($order->default_shipping_method_code)){
					$method=CarrierApiHelper::getServiceCode($order->default_shipping_method_code,$order->order_source);
				}else{
					$method = CarrierApiHelper::getDefaultServiceCode($order->order_source);;
				}
			}
			
			if (in_array($order->order_source, ['ebay']) || empty($allShipcodeMapping[$order->order_source])){
				echo Html::input('',"shipmethod[".$order->order_id."]",$method,['size'=>25]);
			}else if (!empty($allShipcodeMapping[$order->order_source]) && ($allShipcodeMapping[$order->order_source]['type'] == 'text')){
				echo Html::input('',"shipmethod[".$order->order_id."]",$method,['size'=>25]);
			}else{
				//兼容
				if(in_array($order->order_source, ['cdiscount'])){
					$tmp_methods = array_keys($allShipcodeMapping[$order->order_source]['shippingServices']);
					foreach ($tmp_methods as $t_m){
						if(strtoupper($t_m) == strtoupper($method)){
							$method = $t_m;
							if(strtolower($method)!=='other')
								$othermethodDisplay = 'display:none';
							break;
						}
					}
				}
				
				// 手写method 支持选择
				if(in_array($order->order_source, ['amazon']) && !isset($allShipcodeMapping[$order->order_source]['shippingServices'][$method])){
					$allShipcodeMapping[$order->order_source]['shippingServices'][$method] = $method;
				}
				
				$tmp_shippingServices = $allShipcodeMapping[$order->order_source]['shippingServices'];
				$tmp_shippingServices_now = array();
				if(count($tmp_shippingServices) > 0){
					foreach ($tmp_shippingServices as $tmp_shippingServicesKey => $tmp_shippingServicesVal){
						$tmp_shippingServices_now[$tmp_shippingServicesKey] = isset($tmp_shippingServicesVal['service_val']) ? $tmp_shippingServicesVal['service_val'] : $tmp_shippingServicesVal;
					}
				}
				echo Html::dropDownList("shipmethod[".$order->order_id."]",$method,$tmp_shippingServices_now,['data-platform'=>$order->order_source , 'prompt'=>'物流方式','style'=>'width:200px;display:inline-block;','id'=>'carrier_code','class'=>'form-control']);
			}
			?>
			<?php if(in_array($order->order_source, ['cdiscount'])):?>
			<br><span class="cd_othermethod" style="<?=$othermethodDisplay ?>">物流名称 <?=Html::input('',"othermethod[$order->order_id]",@$odship->shipping_method_name,['placeholder'=>'最好填法文或英文运输服务名','size'=>30,'style'=>'width:auto;display:inline-block;','class'=>'form-control'])?></span>
			<?php endif;?>
  	</td>
  	
  	<td style='<?=($allShipcodeMapping[$order->order_source]['web_url_tyep'] != 1 ? 'display:none;' : '') ?>'>
  	</td>
  	
  	
  	<?php if($allShipcodeMapping[$order->order_source]['delivery_msg'] == true):?>
  	<td>
  		发货留言<?=Html::input('',"message[$order->order_id]",'',['size'=>40,'style'=>'width:auto;display:inline-block;','class'=>'form-control'])?>
  	</td>
  	<?php endif;?>
	
</tr>

<tr style="background-color: #d9d9d9;"  >
	<td colspan="5" class="row tr_td_border_bottom" ></td>
</tr>

<?php endforeach;endif; ?>
</table>

<div style='margin: 10px 0px;'>
<center>
<?php // Html::submitButton('标记发货',['class'=>'btn btn-success'])?>
<button type="button" class="btn btn-success save_btn_signshipped" onclick="">标记发货</button>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?=Html::button('取消',['class'=>'btn colse_btn_signshipped'])?>
</center>
</div>

</form>

<!-- 展示该订单的标记历史及结果 -->
<table class="table table-condensed" style="font-size:12px">
<tr>
	<th>订单号</th>
	<th>运单号</th>
	<th>客户参考号</th>
	<th>物流商</th>
	<th>结果</th>
	<th>来源</th>
	<th>错误</th>
	<th>创建时间</th>
	<th>最后标记时间</th>
</tr>
<?php if (count($logs)):foreach ($logs as $log):?>
<tr>
	<td><?=$log->order_id?></td>
	<td><?=$log->tracking_number?></td>
	<td><?=$log->customer_number?></td>
	<td><?=$log->shipping_method_code?></td>
	<td>
		<?php if($log->result == 'false'){?>
			失败
		<?php }elseif($log->result == 'true'){?>
			成功
		<?php }?>
	</td>
	<td><?=$log->addtype?></td>
	<td><?=$log->errors?></td>
	<td><?=date('Y-m-d H:i:s',$log->created)?></td>
	<td><?=date('Y-m-d H:i:s',$log->lasttime)?></td>
</tr>
<?php endforeach;endif;?>
</table>

<?php
}
?>

<script>
	$('[data-platform=amazon]').combobox({removeIfInvalid:false,allNull:true});

	$("select[data-platform=cdiscount][name^='shipmethod[']").change(
			function(){
				var method = $(this).val();
				var tmp = $(this).parents("tr").find("input[name^='trackurl[']:eq(0)");
				var cd_othermethod = $(this).parents("tr").find(".cd_othermethod");
				if(method!=='Other' && method!==''){
					tmp.val('');
					tmp.attr("readonly","readonly");
					cd_othermethod.css("display","none");
				}else{
					tmp.removeAttr("readonly","readonly");
					cd_othermethod.css("display","block");
				}
	});
</script>
