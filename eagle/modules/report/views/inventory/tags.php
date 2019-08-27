<?php

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/report/inventory/tags.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("tags.init();" , \yii\web\View::POS_READY);

?>
<style>
.modal-dialog{
		width: 1000px;
}
</style>
<h4><label>多标签统计</label></h4>

<button class="btn btn-info" style="margin-bottom: 10px;" onclick="tags.addQueryCountWin()">添加统计对象</button>
<button class="btn btn-info" style="margin-bottom: 10px;" onclick="tags.exportExcel()" >导出Excel</button>
<table id="table-report-tags" cellspacing="0" cellpadding="0" width="100%" class="table table-hover">
<tr>
	<th nowrap>序号</th><th nowrap>标签组合</th><th nowrap>有库存SKU数量</th><th nowrap>商品数量</th>
	<th nowrap>品牌数量</th><th nowrap>商品价值</th><th nowrap>操作</th>
</tr>
</table>
<form id="report-inventory-tags-get-excel-form" name="report-inventory-tags-get-excel-form" style="display:none;" method="post">
<input type="hidden" name="hid-TableTagArray" />
</form>