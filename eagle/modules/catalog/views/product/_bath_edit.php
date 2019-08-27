<?php
use eagle\modules\util\helpers\TranslateHelper;
use Qiniu\json_decode;
?>
<style>
.product_info .modal-body {
	max-height: 600px;
	overflow-y: auto;
}
.product_info .modal-dialog {
	width: 70%;
}
#bath_edti_product_table th{
	text-align: center !important;
	white-space: nowrap !important;;
}
#bath_edti_product_table th a{
	color: #428bca;
}
#bath_edti_product_table td , #bath_edti_product_table th{
	padding: 4px !important;
	border: 1px solid rgb(202,202,202) !important;
	vertical-align: middle !important;
	word-break:break-word !important;
}
#bath_edti_product_table tr:hover {
	background-color: #afd9ff !important;
}
#bath_edti_product_table textarea, #bath_edti_product_table select{
    /*height: 50px;*/
   	width: 94%;
   	margin: 5px;
   	resize: none;
}
.edit_info_sml .modal-dialog, .edit_info_battery .modal-dialog{
	width: 300px !important;
}
.table_edit_info_spec1{
    margin:10px 20px;
}
.table_edit_info_spec1 tr{
    height:40px;
   	font-size:15px;
}
.table_edit_info_spec1 td{
	border: none;
}
.table_edit_info_spec1 span{
	width: 120px; 
	text-align: right; 
	display: inline-block;
}
</style>

<form class="form-inline" id="bath_edit_pro_form" name="form2" action="" method="post">
	<table id="bath_edti_product_table" cellspacing="0" cellpadding="0" style="font-size: 12px; "
		class="table table-hover">
		<thead>
		<tr>
			<th nowrap width="<?= $edit_type == 'declaration' ? '250' : '100' ?>px"><?=TranslateHelper::t('商品信息')?></th>
			<?php foreach($edit_col_name as $col_en => $col_cn){?>
			<th nowrap width="100px" col_name='[<?= $col_en ?>]'>
				<span><?=TranslateHelper::t( $col_cn )?></span><br>
				<a class="edit_row" edit_dialog="<?= in_array($col_en, $spec_edit_dialog) ? 'edit_info_spec1' : ($col_en == 'battery' ? 'edit_info_battery' : 'edit_info_sml'); ?>" href="javascript:void(0);" >修改</a>
				<a class="restore_row" href="javascript:void(0);" >还原</a>
				<?php if($col_en != 'battery'){?>
				<a class="clear_row" href="javascript:void(0);" >清空</a>
				<?php }?>
				<?php if($col_en == 'commission_per'){?>
				    <select class="form-control" id="bath_edit_commission_platform" name="bath_edit_commission_platform" style="display: block; margin: 0px;" onchange="productList.bath_edit.change_plat()">
                        <option value="" >请选择平台</option>';
    					<?php foreach($platforms as $plat){
                            echo '<option value="'.$plat.'" >'.$plat.'</option>';
                        }?>
            	  	</select>
				<?php }?>
			</th>
			<?php }?>
			<th nowrap width="80px"><?=TranslateHelper::t('操作') ?></th>
		</tr>
		</thead>
		<tbody>
			<?php foreach($edit_info as $index => $row){?>
	     	<tr name="edit_item_<?=$row['product_id'] ?>" <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
	     		<td>
					<div style="float: left; margin-right: 10px; ">
						<img src="<?=$row['photo_primary']?>" style="width:50px ! important;height:50px ! important">
					</div>
					<div>
						<?= (empty($row['purchase_link']) ? $row['sku'] : '<a href="'.$row['purchase_link'].'" target="_blank">'.$row['sku'].'</a>') ?>
						</br>
						<?=$row['name']?>
					</div>
				</td>
				<?php foreach($edit_col_name as $col_en => $col_cn){?>
				<td>
					<?php if($col_en == 'battery'){?>
					<select class="form-control" name="<?= "item[$index][".$col_en."]" ?>" data-content="<?=$row[$col_en]?>">
  						<option value="N" <?= empty($row[$col_en]) || $row[$col_en] == 'N' ? 'selected' : ''; ?>>否</option>
  						<option value="Y" <?= !empty($row[$col_en]) && $row[$col_en] == 'Y' ? 'selected' : ''; ?>>是</option>
  					</select>
  					<?php }else if($col_en == 'commission_per'){
  					    //整理佣金比例信息
  					    $commission_per = array();
  					    if(!empty($row['addi_info'])){
                            $addi_info = json_decode($row['addi_info'], true);
                            if(!empty($addi_info['commission_per'])){
                                $commission_per = $addi_info['commission_per'];
                            }
                        }
  					?>
  					<textarea name="<?= "item[$index][".$col_en."]" ?>"
  					<?php foreach($commission_per as $key => $val){ 
                            echo "$key='$val' ";
  					    }
  					?>
  					></textarea>
					<?php }else{?>
					<textarea name="<?= "item[$index][".$col_en."]" ?>" data-content="<?=$row[$col_en]?>"><?= $row[$col_en] ?></textarea>
					<?php }?>
				</td>
				<?php }?>
	   			<td style="text-align: center;">
	   				<a class="remove_row" href="javascript:void(0);" >移除</a>
	   			</td>
	   			<td style="display: none">
	   			    <input type="hidden" name="item[<?= $index ?>][product_id]" value="<?=$row['product_id'] ?>" />
	   			</td>
	   		</tr>
	       <?php }?>
		</tbody>
	</table>
</form>

<script>
productList.bath_edit.init();
</script>



