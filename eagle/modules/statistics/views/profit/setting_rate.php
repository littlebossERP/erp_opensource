<?php
use eagle\modules\util\helpers\TranslateHelper;
?>
<style>
.rate_form .modal-body {
	max-height: 600px;
	overflow-y: auto;
}
.rate_form .modal-dialog {
	width: 400px;
}
#save_rate_table th{
	text-align: center !important;
	white-space: nowrap !important;;
}
#save_rate_table th a{
	color: #428bca;
}
#save_rate_table td , #save_rate_table th{
	padding: 4px !important;
	border: 1px solid rgb(202,202,202) !important;
	vertical-align: middle !important;
	word-break:break-word !important;
}
#save_rate_table tr:hover {
	background-color: #afd9ff !important;
}
#save_rate_table .rate{
	width: 100px;
}
</style>

<p style="color: red; line-height: 15px; ">注意：1、设置对RMB的汇率；</p>
<p style="color: red; line-height: 15px; margin-left: 35px; ">2、勾选自定义，即表示之后的订单用设置好的汇率计算，否则，用系统定期更新的汇率；</p>
<form class="form-inline" id="rate_form" name="form2" action="" method="post">
	<table id="save_rate_table" cellspacing="0" cellpadding="0" style="font-size: 12px; "
		class="table table-hover">
		<thead>
		<tr>
			<th nowrap width="130px"><?=TranslateHelper::t('货币')?></th>
			<th nowrap width="40px"><?=TranslateHelper::t('自定义')?></th>
			<th nowrap width="130px"><?=TranslateHelper::t('汇率')?></th>
		</tr>
		</thead>
		<tbody>
			<?php foreach($list as $key => $val){?>
	     	<tr>
	   			<td name="currency" style="text-align:center;"><?= $key ?></td>
	   			<td style="text-align:center;">
		   			<label style="width: 100%; ">
		   				<input type='checkbox' <?= empty($val['type']) ? '' : 'checked'; ?> class='rate_ck' />
		   			</label>
	   			</td>
	   			<td name="rate" style="text-align:center;">
	   				<?php if(!empty($val['type'])){?>
	   					<span style="display: none; "><?= $val['rate'] ?></span>
	   					<input name="rate[]" class="rate" value="<?= $val['rate'] ?>" style="text-align:center;" />
						<input type="hidden" name="currency[]" value="<?= $key ?>" />
	   				<?php }else{?>
	   					<span><?= $val['rate'] ?></span>
	   				<?php }?>
	   			</td>
	   		</tr>
	       <?php }?>
		</tbody>
	</table>
</form>

<script>
$(document).off('click','.rate_ck').on('click','.rate_ck',function(){
	var obj = $(this).parents('tr').first().find('td[name="rate"]');
	if(this.checked){
		var val = obj.find('span').html();
		var currency = obj.prevAll('td[name="currency"]').first().html();
		obj.append('<input name="rate[]" class="rate" value="'+ val +'" style="text-align:center;" />'+
				'<input type="hidden" name="currency[]" value="'+ currency +'" />');
		obj.find('span').css('display', 'none');
	}
	else{
		obj.find('input').remove();
		obj.find('span').css('display', '');
	}
}); 
</script>



