<?php
use eagle\modules\util\helpers\TranslateHelper;
?>
<style>
.product_info .modal-body {
	max-height: 600px;
	overflow-y: auto;
}
.product_info .modal-dialog {
	width: 80%;
}
#create_product_table th{
	text-align: center !important;
	white-space: nowrap !important;;
}
#create_product_table th a{
	color: #428bca;
}
#create_product_table td , #create_product_table th{
	padding: 4px !important;
	border: 1px solid rgb(202,202,202) !important;
	vertical-align: middle !important;
	word-break:break-word !important;
}
#create_product_table tr:hover {
	background-color: #afd9ff !important;
}
   #create_product_table textarea{
   	height: 50px;
   	width: 94%;
   	margin: 5px;
   	resize: none;
   }
</style>

<form class="form-inline" id="creat_pro_form" name="form2" action="" method="post">
	<table id="create_product_table" cellspacing="0" cellpadding="0" style="font-size: 12px; "
		class="table table-hover">
		<thead>
		<tr>
			<th nowrap width="60px"><?=TranslateHelper::t('图片')?></th>
			<th nowrap width="150px" col_name='root_sku[]'>
				<?=TranslateHelper::t('SKU')?><br>
				<a class="restore_row" href="javascript:void(0);" >还原</a>
			</th>
			<th nowrap width="300px" col_name='name[]'>
				<?=TranslateHelper::t('商品名称')?><br>
				<a class="edit_row" href="javascript:void(0);" >修改</a>
				<a class="restore_row" href="javascript:void(0);" >还原</a>
			</th>
			<th nowrap width="100px" col_name='prod_name_ch[]'>
				<span><?=TranslateHelper::t('中文报关名')?></span><br>
				<a class="edit_row" href="javascript:void(0);" >修改</a>
				<a class="clear_row" href="javascript:void(0);" >清空</a>
			</th>
			<th nowrap width="100px" col_name='prod_name_en[]'>
				<?=TranslateHelper::t('英文报关名')?><br>
				<a class="edit_row" href="javascript:void(0);" >修改</a>
				<a class="clear_row" href="javascript:void(0);" >清空</a>
			</th>
			<th nowrap width="50px" col_name='declared_value[]'>
				<?=TranslateHelper::t('申报金额')?><br>
				<a class="edit_row" href="javascript:void(0);" >修改</a>
				<a class="clear_row" href="javascript:void(0);" >清空</a>
			</th>
			<th nowrap width="50px" col_name='declared_weight[]'>
				<?=TranslateHelper::t('重量')?><br>
				<a class="edit_row" href="javascript:void(0);" >修改</a>
				<a class="clear_row" href="javascript:void(0);" >清空</a>
			</th>
			<th nowrap width="80px"><?=TranslateHelper::t('操作') ?></th>
		</tr>
		</thead>
		<tbody>
			<?php foreach($orderInfo as $index=>$row){?>
	     	<tr name="matching_item_<?=$row['order_item_id'] ?>" <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
	   			<td style='text-align:center;'>
					<div style='height: 50px;'><img style='max-height: 50px; max-width: 80px' src='<?=$row['photo_primary_url'] ?>' /></div>
				</td>
				<td>
					<textarea name="root_sku[]" data-content="<?=$row['sku']?>"><?=$row['sku']?></textarea>
				</td>
				<td>
					<textarea name="name[]" data-content="<?=$row['product_name']?>"><?=$row['product_name']?></textarea>
				</td>
				<td>
					<textarea name="prod_name_ch[]" value="" placeholder="默认：礼品"></textarea>
				</td>
				<td>
					<textarea name="prod_name_en[]" value="" placeholder="默认：gift"></textarea>
				</td>
				<td>
					<textarea name="declared_value[]" value="" placeholder="默认：0"></textarea>
				</td>
				<td>
					<textarea name="declared_weight[]" value="" placeholder="默认：50"></textarea>
				</td>
	   			<td style="text-align: center;">
	   				<a class="remove_row" href="javascript:void(0);" >移除</a>
	   			</td>
	   			<td style="display: none">
	   			    <input type="hidden" name="sku[]" value="<?=$row['sku'] ?>" />
	   			    <input type="hidden" name="order_item_id[]" value="<?=$row['order_item_id'] ?>" />
	   				<input type="hidden" name="photo_primary[]" value="<?=$row['photo_primary_url'] ?>" />
	   				<input type="hidden" name="matching_itemid[]" value="<?=$row['matching_itemid'] ?>" />
	   			</td>
	   		</tr>
	   		<!-- <tr style="display: none; ">
	   			<td colspan=8></td>
	   		</tr>
	   		 -->
	       <?php }?>
		</tbody>
	</table>
</form>

<script>
matching.create_pro.init();
</script>



