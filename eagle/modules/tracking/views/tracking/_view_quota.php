<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use eagle\modules\platform\apihelpers\CdiscountAccountsApiHelper;

?>
<style>
.table th, .table td{
	border-top: 1px solid #ddd !important;
	border-left: 1px solid #ddd !important;
	border-right: 1px solid #ddd !important;
	border-bottom: 1px solid #ddd !important;
}
.view-quota .modal-dialog{
	width:800px;
	min-height:400px;
	max-height:80%;
	overflow: auto;
}
.view-quota b{
	font-weight: bold;
}
</style>
<div>
	<div class="alert alert-info" style="text-align: left;line-height:2">
		每一个物流号将会耗费一个查询额度，后续自动更新不会消耗额度。
		<br>
		物流跟踪助手的查询额度用完以后，新增加的物流号将无法查询，此状态的物流号，是系统由于额度不够放弃查询的，请进行批量删除或者批量重新查询(月份交替额度更新后，或升级套餐增加额度后)。
		<br>
		VIP/套餐 收费和额度信息，请查看<a href="/payment/user-account/erp-package-list" target="_blank"><b style="font-size:16px">ERP收费服务</b></a></span>
	</div>
	<?php if(empty($max_import_limit)) $max_import_limit=0;?>
	<?php if(empty($used_count)) $used_count=0;?>
	<table class="table" style="width:100%">
		<tr>
			<th width="50%">当前总可用配额</th>
			<td width="50%"><?=$max_import_limit?></td>
		</tr>
		<tr>
			<th width="50%">目前已使用配额</th>
			<td width="50%"><?=$used_count?></td>
		</tr>
		<tr>
			<th width="50%">剩余可用配额</th>
			
			<td width="50%"><?=$max_import_limit-$used_count?></td>
		</tr>
	</table>
</div>