<?php
use eagle\modules\util\helpers\TranslateHelper;
?>
<style>
.stock_info .modal-body {
	max-height: 600px;
	overflow-y: auto;
}
.stock_info .modal-dialog {
	width: 900px;
}
#edit_stock_table th{
	text-align: center !important;
	white-space: nowrap !important;
}
#edit_stock_table th a{
	color: #428bca;
}
#edit_stock_table td , #edit_stock_table th{
	padding: 4px !important;
	border: 1px solid rgb(202,202,202) !important;
	vertical-align: middle !important;
	word-break:break-word !important;
}
#edit_stock_table tr:hover {
	background-color: #afd9ff !important;
}
#edit_stock_table textarea{
	height: 50px;
	width: 94%;
	margin: 5px;
	resize: none;
}
</style>

<form class="form-inline" id="edit_stock_form" name="form2" action="" method="post">
	<input name="show_time" type="text" value="<?= $show_time ?>" style="display: none;"/>
	<table id="edit_stock_table" cellspacing="0" cellpadding="0" style="font-size: 12px; "
		class="table table-hover">
		<thead>
		<tr>
			<th width="30px"><?=TranslateHelper::t('序号')?></th>
			<th width="60px"><?=TranslateHelper::t('图片')?></th>
			<th width="150px"><?=TranslateHelper::t('SKU')?></th>
			<th width="200px"><?=TranslateHelper::t('产品名称')?></th>
			<th width="100px"><?=TranslateHelper::t('仓库')?></th>
			<th width="100px" col_name='location_grid[]' >
				<span><?=TranslateHelper::t('货架位置')?></span><br>
				<a class="restore_row" href="javascript:void(0);" >还原</a>
				<a class="edit_row" href="javascript:void(0);" >修改</a>
				<a class="clear_row" href="javascript:void(0);" >清空</a>
			</th>
			<th width="100px" col_name='stock_qty[]'>
				<span><?=TranslateHelper::t('库存量')?></span><br>
				<a class="restore_row" href="javascript:void(0);" >还原</a>
				<a class="edit_row" href="javascript:void(0);" >修改</a>
				<a class="reset_row" href="javascript:void(0);" >清零</a>
			</th>
			<th width="100px" col_name='stock_price[]'>
				<span><?=TranslateHelper::t('单价')?></span><br>
				<a class="restore_row" href="javascript:void(0);" >还原</a>
				<a class="edit_row" href="javascript:void(0);" >修改</a>
				<a class="reset_row" href="javascript:void(0);" >清零</a>
			</th>
			<th nowrap width="100px" col_name='safety_stock[]'>
				<span><?=TranslateHelper::t('安全库存')?></span><br>
				<a class="restore_row" href="javascript:void(0);" >还原</a>
				<a class="edit_row" href="javascript:void(0);" >修改</a>
				<a class="reset_row" href="javascript:void(0);" >清零</a>
			</th>
			<th nowrap width="80px"><?=TranslateHelper::t('操作') ?></th>
		</tr>
		</thead>
		<tbody>
			<?php foreach($list as $index => $row){?>
	     	<tr name="stock_<?=$row['prod_stock_id'] ?>" <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
	     		<td><?= $index + 1 ?></td>
	   			<td style='text-align:center;'>
					<div style='height: 50px;'><img style='max-height: 50px; max-width: 80px' src='<?=$row['photo_primary'] ?>' /></div>
				</td>
				<td><?= $row['sku'] ?></td>
				<td><?= $row['name'] ?></td>
				<td><?= empty($warehouse[$row['warehouse_id']]) ? '' : $warehouse[$row['warehouse_id']] ?></td>
				<td>
					<input name="location_grid[]" value="<?= $row['location_grid'] ?>" data-content="<?=$row['location_grid']?>" style="width: 80px;"></input>
				</td>
				<td>
					<input name="stock_qty[]" value="<?= $row['qty_in_stock'] ?>" data-content="<?=$row['qty_in_stock']?>" style="width: 50px;" ></input>
				</td>
				<td>
					<input name="stock_price[]" value="<?= $row['average_price'] ?>" data-content="<?=$row['average_price']?>" style="width: 50px;" ></input>
				</td>
				<td>
					<input name="safety_stock[]" value="<?= $row['safety_stock'] ?>" data-content="<?=$row['safety_stock']?>" style="width: 50px;" ></input>
				</td>
	   			<td style="text-align: center;">
	   				<a class="remove_row" href="javascript:void(0);" >移除</a>
	   			</td>
	   			<td style="display: none; ">
	   				<input name="prod_stock_id[]" value="<?= $row['prod_stock_id'] ?>" />
	   			</td>
	   		</tr>
	       <?php }?>
		</tbody>
	</table>
</form>

<div class="modal-body tab-content" id="dialog3" style="display:none;">
	<textarea class="edit_stock_edit_value" value="" placeholder="请输入批量修改的内容" style="width: 100%; height: 60px; "></textarea>
</div>

<script>
edit_stock.init();
</script>



