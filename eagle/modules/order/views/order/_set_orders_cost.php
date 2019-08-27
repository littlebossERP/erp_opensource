<?php
use yii\helpers\Html;
use yii\helpers\Url;


$this->registerJs("initFormValidateInput();" , \yii\web\View::POS_READY);
$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
$this->registerJs("$('.item_img,.item_name').popover();" , \yii\web\View::POS_READY);
?>
<style>
.profit-order .modal-dialog{
	width:600px;
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
	margin-left: 5px;
}
</style>
<form style="width:100%;display:inline-block;text-align:center" id="order_cost_form">
	<!-- 设置采购价，其他费用 -->
	<input type="hidden" name="order_ids" value="<?=implode(',',$order_ids) ?>">
	<table style="width:100%">
		<tr width="100%">
			<td width="50%" style="text-align:right;"><button type="button" onclick="changeType(0 , this)" class="btn btn-success type-btn" style="margin:5px">基于商品模块的商品采购价</button></td>
			<td width="50%" style="text-align:left;"><button type="button" onclick="changeType(1 , this)" class="btn type-btn" style="margin:5px">基于当次订单的商品采购价</button><span qtipkey="profit_notice"></span></td>
		</tr>
		<tr width="100%">
			<td width="50%" style="text-align:right;">
				<button type="button" onclick="importProfitData('product_cost')" class="btn-xs btn-info type-btn" style="margin-right:5px;margin-bottom:1px;">导入商品采购价</button><br>
				<button type="button" onclick="importProfitData('logistics_cost')" class="btn-xs btn-info type-btn" style="margin-right:5px;margin-top:1px;">导入订单物流成本</button>
			</td>

			<td width="50%">
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
			<td class="purchase_price"><input type="text" name="purchase_price[]" style="width:100px;" value="<?=empty($item['purchase_price'])?'':$item['purchase_price']?>"><span class="required_atten">*</span></td>
			<td class="price_based_on_order" style="display:none;"><input type="text" name="price_based_on_order[]" style="width:100px;" value="<?=empty($item['price_based_on_order'])?'':$item['price_based_on_order']?>"><span class="required_atten">*</span></td>
			<td class="additional_cost"><input type="text" name="additional_cost[]" style="width:100px;" value="<?=empty($item['additional_cost'])?'':$item['additional_cost']?>"></td>
		</tr>
	<?php }?>
	
	</table>
	
	<!-- 设置物流成本，物流重量 -->
	<table class="table order_logistics_info_tb">
		<tr>
			<th width="31%">平台订单号</th>
			<th width="31%">物流成本</th>
			<th width="31%">包裹重量(克)</th>
			<th width="7%"></th>
		</tr>
	<?php foreach ($need_logistics_cost as $order_no=>$od){?>
		<tr>
			<td><?=$order_no?><input type="hidden" name="order_no[]" value="<?=$order_no?>"></td>
			<td class="logistics_cost"><input type="text" name="logistics_cost[<?=$order_no?>]" value="<?=empty($od['logistics_cost'])?'':$od['logistics_cost']?>" onchange="initCancelBtn(this,'<?=$od['order_id']?>')"><span class="required_atten">*</span></td>
			<td class="logistics_weight"><input type="text" name="logistics_weight[<?=$order_no?>]" value="<?=empty($od['logistics_weight'])?'':$od['logistics_weight']?>"></td>
			<td id="cancel_order_<?=$od['order_id']?>" title="取消对此订单的统计"><button type="button" class="btn-xs btn-transparent" onclick="cancelProfitOrder('<?=$od['order_id']?>')" style="display:none;"><span class="glyphicon glyphicon-remove" style="color:red;border:solid 1px #9DE1FD;padding:1px;"></span></button></td>
		</tr>
	<?php } ?>
	</table>
	
</form>
<div style="width:100%;display:inline-block;text-align:center">
	<button type="button" class="btn btn-primary" onclick="setCostAndProfitOrder()">保存并开始计算</button>
	<button type="button" class="btn btn-warning" onclick="cancelProfit()">取消</button>
</div>

<script type="text/javascript">
function changeType(type,obj){
	$.showLoading();
	$(".type-btn").removeClass("btn-success");
	$(obj).addClass("btn-success");
	if(type==0){
		//$(".price_based_on_order input").val("");
		$(".price_based_on_order").hide();
		$(".additional_cost").show();
		$(".purchase_price").show();
		$("input[name='price_type']").val(0);
		$('.purchase_price input').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
		$('.additional_cost input').formValidation({validType:['trim','amount'],tipPosition:'left'});
		$('.price_based_on_order input').formValidation({validType:['trim','amount'],tipPosition:'left',required:false});
	}
	if(type==1){
		//$(".purchase_price input").val("");
		$(".purchase_price").hide();
		$(".price_based_on_order").show();
		$(".additional_cost").hide();
		$("input[name='price_type']").val(1);
		$('.purchase_price input').formValidation({validType:['trim','amount'],tipPosition:'left',required:false});
		$('.additional_cost input').formValidation({validType:['trim','amount'],tipPosition:'left'});
		$('.price_based_on_order input').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
	}
	$.hideLoading();
}

function initFormValidateInput(){
	$('.purchase_price input').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
	$('.additional_cost input').formValidation({validType:['trim','amount'],tipPosition:'left'});
	$('.logistics_cost input').formValidation({validType:['trim','amount'],tipPosition:'left',required:true});
	$('.logistics_weight input').formValidation({validType:['trim','amount'],tipPosition:'left'});
	
	$('.logistics_cost input').each(function(){
		if(this.value == '')
			$(this).nextAll("td[id^='cancel_order_'] button").show();
		else
			$(this).nextAll("td[id^='cancel_order_'] button").hide();
	});
}

function initCancelBtn(obj,id){
	var value = $(obj).val();
	if(value=='' || typeof(value)=='undefined'){
		$("#cancel_order_"+id+" button").show();
	}else{
		$("#cancel_order_"+id+" button").hide();
	}
}

function setCostAndProfitOrder(){
	$.showLoading();
	if (!$('#order_cost_form').formValidation('form_validate')){
		$.hideLoading();
		bootbox.alert(Translator.t('录入格式不正确或不完整!'));
		return false;
	}
	$.ajax({
		type: "POST",
		dataType: 'json',
		url : '/order/order/set-cost-and-profit-order',
		data:$("#order_cost_form").serialize(),
		success: function (result) {
			$.hideLoading();
			if(result.success && result.calculated_profit){
				if(result.message=='')
					var msg='操作成功！';
				else
					var msg=result.message;
				bootbox.alert({
					buttons: {
						ok: {
							label: 'OK',
							className: 'btn-primary'
						}
					},
					message: msg,
					callback: function() {
						window.location.reload();
					}
				});
			}
			else{	
				bootbox.alert({
					buttons: {
						ok: {
							label: 'OK',
							className: 'btn-primary'
						}
					},
					message: '有部分设置保存失败,请再次设置失败部分：'+result.message,
					callback: function() {
						$('.profit-order').modal('hide');
						$('.profit-order').on('hidden.bs.modal', '.modal', function(event) {
							$(this).removeData('bs.modal');
						});
					}
				});
			}
		},
		error: function(){
			$.hideLoading();
			bootbox.alert("出现错误，请联系客服求助...");
			return false;
		}
	
	});
}

function cancelProfit(){
	$('.profit-order').modal('hide');
	$('.profit-order').on('hidden.bs.modal', '.modal', function(event) {
		$(this).removeData('bs.modal');
	});
}

function importProfitData(type){
	if(type=='product_cost'){
		var template_url = "/template/商品成本录入.xls";
		var form_url = "/order/order/excel2-order-cost";
	}
	if(type=='logistics_cost'){
		var template_url = "/template/订单物流成本录入.xls";
		var form_url = "/order/order/excel2-order-cost";
	}
	if (typeof(importFile) != 'undefined'){
		importFile.showImportModal(
			Translator.t('请选择导入的excel文件') , 
			form_url , 
			template_url , 
			type,
			function(result){
				var ErrorMsg = "";
				if(typeof(result)=='object'){
					if(result.success){
						var success = 1;
					}else
						var success = 0;
					
					if(typeof(result.message)!=='undefined'){
						ErrorMsg += result.message;
					}
				}else{
					ErrorMsg += result;
					var success = 0;
				}
				if (ErrorMsg != "" && success!==1){
					ErrorMsg= "<div style='height: 600px;overflow: auto;'>"+ErrorMsg+"</div>";
					bootbox.alert({
						buttons: {
							ok: {
								label: 'OK',
								className: 'btn-primary'
							}
						},
						message: ErrorMsg,
						callback: function() {
							$('.profit-order').modal('hide');
							$('.profit-order').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});
							doaction('calculat_profit');
						}
					});
				}else{
					bootbox.alert({
						buttons: {
							ok: {
								label: 'OK',
								className: 'btn-primary'
							}
						},
						message: Translator.t('导入成功,点击确认刷新窗口数据'),
						callback: function() {
							$('.profit-order').modal('hide');
							$('.profit-order').on('hidden.bs.modal', '.modal', function(event) {
								$(this).removeData('bs.modal');
							});
							doaction('calculat_profit');
						}
					});
				}
			}
		);
	}else{ bootbox.alert(Translator.t("没有引入相应的文件!")); }
}

function cancelProfitOrder(id){
	$("#cancel_order_"+id).parent().remove();
	$("input[name='order_id[]'][value='"+id+"']").prop('checked',false);
}

</script>