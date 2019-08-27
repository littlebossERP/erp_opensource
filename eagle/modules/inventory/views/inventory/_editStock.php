<?php

?>
<style>
.title_name{
	text-align: right;
	width: 80px;
	font-weight: 600;
}
</style>

<table class="table edit_stock_info" style="margin-bottom: 0px; max-width: 600px; ">
	<tr>
		<td rowspan="7" style="width: 200px; ">
		    <div style="height: 250px; ">
				<img style="max-height: 250px; max-width: 200px;" src="<?= $stock['photo_primary'] ?>">
			</div>
		</td>
		<td class="title_name">SKU：</td>
		<td><?= $stock['sku'] ?></td>
	</tr>
	<tr>
		<td class="title_name">产品名称：</td>
		<td><?=$stock['name'] ?></td>
	</tr>
	<tr>
		<td class="title_name">仓库：</td>
		<td><?= empty($warehouse[$stock['warehouse_id']]) ? '' : $warehouse[$stock['warehouse_id']] ?></td>
	</tr>
	<tr>
		<td class="title_name">货架位置：</td>
		<td><input name="edit_location_grid" value="<?= $stock['location_grid'] ?>" /></td>
	</tr>
	<tr>
		<td class="title_name">库存量：</td>
		<td><input name="edit_stock_qty" value="<?= $stock['qty_in_stock'] ?>" /></td>
	</tr>
	<tr>
		<td class="title_name">单价：</td>
		<td><input name="edit_stock_price" value="<?= $stock['average_price'] ?>" /></td>
	</tr>
	<tr>
		<td class="title_name">安全库存：</td>
		<td><input name="edit_safety_stock" value="<?= $stock['safety_stock'] ?>" /></td>
	</tr>
</table>

<input id="edit_prod_stock_id" type="text" value="<?=$stock['prod_stock_id'] ?>" style="display: none;"/>
<input id="show_time" type="text" value="<?= $show_time ?>" style="display: none;"/>
<div class="modal-footer" style="border-top: 1px solid #e5e5e5; ">
	<button id="btn_edit_stock" type="button" class="btn btn-primary">保存</button>
	<button class="btn-default btn modal-close">关 闭</button>
</div>