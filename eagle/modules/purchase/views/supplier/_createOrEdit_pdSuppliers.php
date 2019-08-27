<?php

use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\UserHelper;

$this->registerJs("pd_suppliers.list.innerInit();" , \yii\web\View::POS_READY);

?>
<style>
.create_new_pd_suppliers .modal-dialog{
	width: 900px;
	max-height: 700px;
	overflow-y: auto;	
}
#create_new_pd_suppliers td{
	padding:4px 0px !important;
	margin:0px !important;
	vertical-align: middle !important;
}
#create_new_pd_suppliers .eagle-form-control{
	padding:0px !important;
	margin:0px !important;
	vertical-align: middle !important;
}
#create_new_pd_suppliers table.table input,#supplier_model_form table.table select{
	width:100%;
}
.content_lfet{
	float:left;
}
.content_right{
	float:right;
}
#create_new_pd_suppliers input.ui-combobox-input{
	width: 95% !important;
}
#create_new_pd_suppliers td,#create_new_pd_suppliers th{
	vertical-align: middle !important;
	text-align: center !important;
}
span.eagle-form-control{
	border:0px !important;
}
</style>
<FORM id="create_new_pd_suppliers" role="form">
	<div style="float:left;width:100%;margin: 5px 0px;">
		<div class="div-input-group" style="width:200px">
			<div style="float:left;width:100%">
				<select name='supplier_id'  class="eagle-form-control" style="width:100%">
					<option value="0"><?= TranslateHelper::t("请选择供应商")?></option>
					<?php foreach($supplierData as $anSupplier):?>
					<option value="<?= $anSupplier['supplier_id']?>"><?=$anSupplier['name']?></option>
					<?php endforeach;?>
				</select> 
			</div>
		</div>
		<div class="btn-group" style="font-size:12px;">
			<button type="button" class="btn-xs btn-primary" onclick="pd_suppliers.list.selectPurchaseProd()" style="height:28px;">
				<span class="glyphicon glyphicon-search" style="vertical-align:middle;height: 16px;"></span> 
				<?=TranslateHelper::t('选择商品')?>
			</button>
		</div>
	</div>

	<table class="table" style="width:100%;margin:5px 0px;font-size:12px;">
		<tr>
			<th width="80px"><?=TranslateHelper::t('图片')?></th>
			<th width="150px">sku</th>
			<th width="250px"><?=TranslateHelper::t('商品名称')?></th>
			<th width="120px"><?=TranslateHelper::t('首选供应商报价')?></th>
			<th width="200px"><?=TranslateHelper::t('添加报价')?></th>
			<th width="100px"><?=TranslateHelper::t('操作')?></th>
		</tr>
	</table>
	<div class="sku_area" style="display:none;"></div>
</FORM>

<script>

</script>
