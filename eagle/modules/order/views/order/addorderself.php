<?php

use yii\helpers\Html;
use eagle\modules\order\models\OdOrder;
?>
<style>
.table th{
	text-align: center;
}
.table td{
	text-align: left;
}

table{
	font-size:12px;
}
.table>tbody td{
color:#637c99;
}
.table>tbody a{
color:#337ab7;
}
.table>thead>tr>th {
height: 35px;
vertical-align: middle;
}
.table>tbody>tr>td {
height: 35px;
vertical-align: middle;
}
</style>
<div class="tracking-index col2-layout">
<div class="content-wrapper" >
<div class="container">
	<form action="" method="post">
		<div class="row">
		  	<div class="col-md-2"><label>基本信息</label></div>
		  	<div class="col-md-10">
			</div>
		</div>
		<div class="row">
		  	<div class="col-md-2">订单来源</div>
		  	<div class="col-md-10">
		  		<?php 
					$sources=[
						'ebay'=>'eBay',
						'wish'=>'Wish',
						'amazon'=>'Amazon',
						'aliexpress'=>'Aliexpress',
						'custom'=>'Custom'
					];
				?>
				<?=Html::dropDownList('order_source','custom',$sources)?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">订单状态</div>
		  	<div class="col-md-10">
		  		<?=Html::dropDownList('order_status',null,OdOrder::$status)?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">买家账号</div>
		  	<div class="col-md-10">
		  		<?=Html::textInput('source_buyer_user_id')?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">订单总金额</div>
		  	<div class="col-md-10">
		  		<?=Html::textInput('grand_total','0.99')?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">订单运费</div>
		  	<div class="col-md-10">
		  		<?=Html::textInput('shipping_cost','0.00')?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">订单金额币种</div>
		  	<div class="col-md-10">
		  		<?=Html::dropDownList('currency','USD',['USD'=>'USD','EUR'=>'EUR','AUD'=>'AUD','GBP'=>'GBP'])?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">原始订单ID</div>
		  	<div class="col-md-10">
		  		<?=Html::textInput('order_source_order_id','0')?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">eBay原始订单SRN</div>
		  	<div class="col-md-10">
		  		<?=Html::textInput('order_source_srn','0')?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2"><label>收货地址信息</label></div>
		  	<div class="col-md-10">
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">买家姓名</div>
		  	<div class="col-md-10">
		  	<?=Html::textInput('consignee')?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">Email</div>
		  	<div class="col-md-10">
		  	<?=Html::input('email','consignee_email','',['size'=>'28'])?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">国家</div>
		  	<div class="col-md-10">
		  	<?=Html::textInput('consignee_country')?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">国家代码</div>
		  	<div class="col-md-10">
		  	<?=Html::textInput('consignee_country_code')?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">州</div>
		  	<div class="col-md-10">
		  	<?=Html::textInput('consignee_province')?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">城市</div>
		  	<div class="col-md-10">
		  	<?=Html::textInput('consignee_city')?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">地址1</div>
		  	<div class="col-md-10">
		  	<?=Html::textInput('consignee_address_line1','',['size'=>'48'])?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">地址2</div>
		  	<div class="col-md-10">
		  	<?=Html::textInput('consignee_address_line2','',['size'=>'48'])?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">邮编</div>
		  	<div class="col-md-10">
		  	<?=Html::textInput('consignee_postal_code')?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">电话</div>
		  	<div class="col-md-10">
		  	<?=Html::textInput('consignee_phone')?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2"><label>商品信息</label></div>
		  	<div class="col-md-10">
			</div>
		</div>
		<div class="row">
			<div class="col-md-2">商品</div>
		  	<div class="col-md-10">
			  	<table class="table table-bordered" id="TableTransactionModify">
				<tr>
					<td>标题</td>
					<td>SKU</td>
					<td>数量</td>
					<td>价格</td>
					<td><?=Html::button('添加商品',['onclick'=>"TableTransactionModifyAdd()"]);?></td>
				</tr>
				<tr id="new" style="display:none">
					<td style="border:1px solid #ddd"><?=Html::textInput('item[product_name][]')?></td>
					<td style="border:1px solid #ddd"><?=Html::textInput('item[sku][]')?></td>
					<td style="border:1px solid #ddd"><?=Html::textInput('item[quantity][]')?></td>
					<td style="border:1px solid #ddd"><?=Html::textInput('item[price][]')?></td>
					<td style="border:1px solid #ddd">
						<?=Html::input('hidden','item[itemid][]')?>
						<?=Html::button('删除',['onclick'=>"removeTransaction(this)"])?>
					</td>
				</tr>
				</table>
			</div>
		</div>
		<div class="row">
			<div class="col-md-2"></div>
		  	<div class="col-md-10">
		  		<?=Html::SubmitButton('提交',['class'=>'btn btn-primary'])?>
			</div>
		</div>
	</form>
</div>
</div>
</div>
<table>
	<tr id="new" style="display:none">
		<td style="border:1px solid #ddd"><?=Html::textInput('item[product_name][]')?></td>
		<td style="border:1px solid #ddd"><?=Html::textInput('item[sku][]')?></td>
		<td style="border:1px solid #ddd"><?=Html::textInput('item[quantity][]')?></td>
		<td style="border:1px solid #ddd"><?=Html::textInput('item[price][]')?></td>
		<td style="border:1px solid #ddd">
			<?=Html::input('hidden','item[itemid][]')?>
			<?=Html::button('删除',['onclick'=>"removeTransaction(this)"])?>
		</td>
	</tr>
</table>
<script>
function TableTransactionModifyAdd(){
	$('#TableTransactionModify').append($('#new').clone().show());
}
function  removeTransaction(obj){
	$(obj).parent().parent().remove();
}
</script>