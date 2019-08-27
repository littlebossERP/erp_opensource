<?php
use yii\helpers\Html;
use yii\helpers\Url;

$baseUrl = \Yii::$app->urlManager->baseUrl;
$this->registerJsFile($baseUrl."/js/project/statistics/profit/setorderscost.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerJs("setorderscost.init();" , \yii\web\View::POS_READY);

$this->registerJs("$('.item_img,.item_name').popover();" , \yii\web\View::POS_READY);
?>
<style>
.profit-order .modal-dialog{
	width:700px;
	min-height:400px;
	max-height:80%;
	overflow: auto;
}
.required_atten{
	color: red;
	font-size: 14px;
	font-weight: bolder;
}
.exchange_data_tb th, .exchange_data_tb td{
	border: 1px solid grey;
	padding: 2px;
}
.exchange_data_tb th{
	font-weight: 600;
    font-size: 12px;
    line-height: 1.5;
}
.exchange_data_tb{
	margin:auto;
}
</style>
<form style="width:100%;display:inline-block;text-align:center" id="order_cost_form">
	<!-- 设置采购价，其他费用 -->
	<input type="hidden" name="order_ids" value="<?=implode(',',$order_ids) ?>">
	<table style="width:100%">
		<tr width="100%">
			<td width="50%" style="text-align:right;"><button type="button" onclick="setorderscost.changeType(0 , this)" class="btn btn-success type-btn" style="margin:5px">基于商品模块的商品采购价</button></td>
			<td width="50%" style="text-align:left;"><button type="button" onclick="setorderscost.changeType(1 , this)" class="btn type-btn" style="margin:5px">基于当次订单的商品采购价</button><span qtipkey="profit_notice"></span></td>
		</tr>
		<tr width="100%">
			<td colspan="2">
    			<?php if(!empty($exchange_data)){
    				echo "<table class='exchange_data_tb'><tr><th>销售货币兑RMB汇率:</th><th>汇损:</th></tr>";
    					foreach($exchange_data as $currency => $exchange){?>
    						<tr>
    							<td><?=$currency?><input type="text" name="exchange[<?=$currency?>]" value="<?=$exchange?>" style="width:60px"></td>
    							<td><input type="text" name="exchange_loss[<?=$currency?>]" value="<?=!isset($exchange_loss[$currency])?'':$exchange_loss[$currency]?>" style="width:30px">%</td>
    						</tr>
    					<?php }
    				echo "</table>";
    			} ?>
			</td>
		</tr>
	</table>
	<input type="hidden" name="price_type" value="">
	<table class="table order_item_info_tb" style="margin-top:10px">
		<tr>
			<th width="150px">SKU</th>
			<th width="50px">图片</th>
			<th width="100px" class="purchase_price">采购价</th>
			<th width="100px" class="price_based_on_order" style="display:none;">当次采购价</th>
			<th width="100px" class="additional_cost">其他成本</th>
		</tr>
	<?php foreach ($need_set_price as $sku=>$item){?>
		<tr>
			<td><div class="item_name" <?=!empty($item['name'])?'data-toggle="popover" data-content="'.$item['name'].'" data-html="true" data-trigger="hover"':''?>>
					<span <?=empty($item['unExisting'])?'style="cursor: pointer;"':''?>><?=$sku?></span>
					<input type="hidden" name="sku[]" value="<?=$sku?>"><?php if(!empty($item['unExisting'])) echo '<span class="exception_210" title="SKU未在商品模块建立，采购价只会记录到本次订单" style="cursor:pointer"></span>'; ?>
				<div>
			</td>
			<td><img class="item_img" src='<?=empty($item['img'])?'':$item['img']?>' style="width:50px;height:50px;" <?=!empty($item['img'])?'data-toggle="popover" data-content="<img src=\''.$item['img'].'\' style=\'width:250px\'>" data-html="true" data-trigger="hover"':''?> ></td>
			<td class="purchase_price"><input type="text" name="purchase_price[]" style="width:100px;" value="<?=empty($item['purchase_price']) ? '0.00' : $item['purchase_price']?>"><span class="required_atten">*</span></td>
			<td class="price_based_on_order" style="display:none;"><input type="text" name="price_based_on_order[]" style="width:100px;" value="<?=empty($item['price_based_on_order']) ? '0.00' : $item['price_based_on_order']?>"><span class="required_atten">*</span></td>
			<td class="additional_cost"><input type="text" name="additional_cost[]" style="width:100px;" value="<?=empty($item['additional_cost']) ? '' : $item['additional_cost']?>"></td>
		</tr>
	<?php }?>
	
	</table>
	
	<!-- 设置物流成本，物流重量 -->
	<table class="table order_logistics_info_tb">
		<tr>
		    <th width="15%">小老板订单号</th>
			<th width="30%">平台订单号</th>
			<th width="30%">物流成本</th>
			<th width="21%">包裹重量(克)</th>
			<th width="7%"></th>
		</tr>
	<?php foreach ($need_logistics_cost as $order_no=>$od){?>
		<tr>
		    <td><?=preg_replace('/^0+/','', $od['order_id'])?><input type="hidden" name="order_id[]" value="<?=preg_replace('/^0+/','', $od['order_id'])?>"></td>
			<td><?=$od['order_source_order_id']?><input type="hidden" name="order_no[]" value="<?=$od['order_source_order_id']?>"></td>
			<td class="logistics_cost"><input type="text" name="logistics_cost[<?=$order_no?>]" value="<?=empty($od['logistics_cost']) ? '0.00' : $od['logistics_cost']?>" onchange="initCancelBtn(this,'<?=$od['order_id']?>')"><span class="required_atten">*</span></td>
			<td class="logistics_weight"><input type="text" name="logistics_weight[<?=$order_no?>]" value="<?=empty($od['logistics_weight'])?'':$od['logistics_weight']?>"></td>
			<td id="cancel_order_<?=$od['order_id']?>" title="取消对此订单的统计"><button type="button" class="btn-xs btn-transparent" onclick="setorderscost.cancelProfitOrder('<?=$od['order_id']?>')" style="display:none;"><span class="glyphicon glyphicon-remove" style="color:red;border:solid 1px #9DE1FD;padding:1px;"></span></button></td>
		</tr>
	<?php } ?>
	</table>
	
</form>
<div style="width:100%;display:inline-block;text-align:center">
	<button type="button" class="btn btn-primary" onclick="setorderscost.setCostAndProfitOrder()">保存并开始计算</button>
	<button type="button" class="btn btn-warning" onclick="setorderscost.cancelProfit()">取消</button>
</div>