<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\purchase\helpers\PurchaseHelper;
use eagle\modules\purchase\helpers\PurchaseShippingHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/purchase/purchase/purchaseOrderViewOrUpdate.js");
$this->registerJs("purchaseOrder.updateorview.initWidget()", \yii\web\View::POS_READY);

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);

$shippingname = PurchaseShippingHelper::getShippingModeById($model->delivery_method);
if($shippingname<>null)
	$shippingname = $shippingname->shipping_name;
else
	$shippingname='';
?>

<style>
.stock_in_dialog .modal-dialog {
	width: 1000px;
}
div .purchase_product_detail{
	height: 400px;
	overflow-y:scroll;
}
div .tb_title{
	width: 100px;
	display: inline-block;
	font-weight: 600;
    font-size: 15px;
	text-align: right;
}
.purchase_product_detail .table th{
	font-size: 15px;
    text-align: center;
}
.purchase_product_detail th a{
	color: #428bca;
}
</style>

<form id="update-purchase-in-stock-form" method="post" class="form-group" > 
	<input type="hidden" name="purchase_id" value="<?= $model->id?>">
	<div style="width:100%;clear:both;">
		<table style="width:100%;font-size:12px;" class="table purchase_order_prods">
			<tr>
				<td><div class="tb_title">采购单号：</div><?= $model->purchase_order_id?></td>
				<td><div class="tb_title">仓库：</div><?= $warehouse[empty($model->warehouse_id)?0:$model->warehouse_id] ?></td>
				<td><div class="tb_title">供应商：</div><?= $model->supplier_name?></td>
			</tr>
			<tr>
				<td><div class="tb_title">采购时间：</div><?= $model->create_time?></td>
				<td><div class="tb_title">采购人员：</div><?= $model->capture_user_name?></td>
				<td><div class="tb_title">采购状态：</div><?= empty($purchaseStatus[$model->status]) ? '' : $purchaseStatus[$model->status]?></td>
			</tr>
			<tr>
				<td colspan="3">
					<div class="tb_title" style="float: left">采购备注：</div>
					<div style="border:1px solid #ccc;padding-left:10px; min-height: 40px; max-height:100px; overflow-y: scroll;" ><?=$model->comment ?></div>
				</td>
			</tr>
			<tr>
				<td colspan="3">
					<div class="tb_title" style="float: left">入库备注：</div>
					<textarea type="text" name="purchase_comment" value="<?=$model->in_stock_comment ?>" style="width: 60%; border: 1px solid #b9d6e8" ><?=$model->in_stock_comment ?></textarea>
				</td>
			</tr>
		</table>
	</div>
	<div class="purchase_product_detail" style="width:100%;clear:both;padding-top:5px;">
		<table style="width:100%;font-size:12px;" class="table-hover table purchase_order_prods">
			<tr style="width:100%;">
				<th style="width:500px;">商品信息</th>
				<th style="width:80px;">采购单价</th>
				<th style="width:80px;">采购数</th>
				<th style="width:80px;">已入库数</th>
				<th style="width:100px;">
    				本次入库数<br>
    				<a class="in_stock_col" href="javascript:void(0);" >全部入库</a>
    				<a class="restore_col" href="javascript:void(0);" >清空</a>
				</th>
			</tr>
			<?php 
			$index=0;
			foreach($detail as $item){
				$surplus_qty = ($item['qty'] - $item['in_stock_qty']) > 0 ? ($item['qty'] - $item['in_stock_qty']) : 0;
			?>
			<tr style="<?= $surplus_qty==0 ? 'background-color: #dff0d8; ' : '' ?>">
				<td>
					<div style="float: left; margin-right: 10px; ">
						<img src="<?=$item['img']?>" style="width:50px ! important;height:50px ! important">
					</div>
					<div>
						<?= (empty($item['purchase_link']) ? $item['sku'] : '<a href="'.$item['purchase_link'].'" target="_blank">'.$item['sku'].'</a>') ?>
						</br>
						<?=$item['name']?>
					</div>
					<input type="hidden" name="prod[<?=$index?>][sku]" value="<?= $item['sku'] ?>">
				</td>
				<td style="text-align: center"><?=$item['price']?></td>
				<td style="text-align: center"><?=$item['qty']?></td>
				<td style="text-align: center"><?=$item['in_stock_qty']?></td>
				<td>
				    <input name="prod[<?=$index?>][in_qty]" type="number" value="0"" class="form-control" pattern="^\d*$"
				    	surplus_qty=<?= $surplus_qty ?> />
				</td>
			</tr>
			<?php 
			$index++;
			}?>
		</table>
	</div>
</form>

<script>
$(function() {
	$(".purchase_product_detail .in_stock_col").click(function(){
		$('input[name$="][in_qty]"]').each(function(){
			var surplus_qty = $(this).attr('surplus_qty');
			$(this).val(surplus_qty);
		});
	});

	$(".purchase_product_detail .restore_col").click(function(){
		$('input[name$="][in_qty]"]').val('0');
	});
	
});
</script>