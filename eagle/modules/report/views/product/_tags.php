<?php

use eagle\components\DateSelectWidget;

$this->registerCssFile(\Yii::getAlias('@web').'/js/project/report/jquery-ui-1.10.4.custom.min.css' , ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/report/report.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/report/product/tags.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("tags.init();" , \yii\web\View::POS_READY);

?>
<style>
.modal-dialog{
		width: 1000px;
}
</style>

<table>
<tr>
	<td>
	请选择销售店
		<select id="report-product-tag-select">
			<option selected="selected" value="0-0">(全部)</option>
			<?php 
			if (count($shopArr) > 0){
				foreach ($shopArr as $shop){
			?>
			<option value='<?=$shop['sourceAndId'] ?>'><?=$shop['sourceAndId'] ?></option>
			<?php
				}
			}
			?>
		</select>
	</td>
	<td style="width: 600px;">
		<span style="margin-left: 10px;">
			<?php echo DateSelectWidget::widget(); ?>
		</span>
	</td>
	<td><button class="btn btn-info" style="margin-bottom: 10px;" onclick="tags.exportExcel()">导出Excel</button></td>
</tr>
<tr>
	<td>
		<button class="btn btn-info" style="margin-bottom: 10px;" onclick="tags.addQueryCountWin()">添加统计对象</button>
	</td>
</tr>
</table>

<div>
<table cellspacing="0" cellpadding="0" width="100%" class="table table-hover" id="table-report-tags">
<tr>
<th>序号</th><th>标签</th><th>有销售SKU数量</th><th>销售商品数量</th><th>品牌数量</th>
<th>销售总金额</th><th>操作</th>
</tr>

</table>
</div>

<form id="report-inventory-tags-get-excel-form" name="report-inventory-tags-get-excel-form" style="display:none;" method="post">
<input type="hidden" name="hid-TableTagArray" />
</form>
