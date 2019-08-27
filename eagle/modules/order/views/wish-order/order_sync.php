<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
// $this->registerJsFile($baseUrl."js/project/order/aliexpressOrder/order_sync.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile(\Yii::getAlias('@web').'/css/order/order.css');
$statusMapping = [
''=>TranslateHelper::t('全部'),
'P'=>TranslateHelper::t('等待同步'),
'S'=>TranslateHelper::t('同步中'),
// ''=>TranslateHelper::t('同步成功'),
'F'=>TranslateHelper::t('同步失败'),
'C'=>TranslateHelper::t('同步完成'),
];
?>
<style>
<!--

-->
.large-box  .modal-dialog{
	width: 1200px;
}

.large-box .modal-body{
	max-height: 800px;
	overflow-y: auto;	
}
</style>


<div class="content-wrapper">
<table class="table">
<thead>
	<tr>
		<th><?= TranslateHelper::t('账号')?></th>
		<th><?= TranslateHelper::t('同步状态')?></th>
		<th><?= TranslateHelper::t('最近同步时间')?></th>
		<th><?= TranslateHelper::t('错误信息')?></th>
	</tr>
</thead>
<tbody>
<?php foreach($sync_list as $account_name=>$row):?>
	<tr>
		<td><?=$account_name?></td>
		<td><?=empty($statusMapping[$row['oq_status']])?$row['oq_status']:$statusMapping[$row['oq_status']];?></td>
		<td><?=$row['last_order_success_retrieve_time']?></td>
		<td><?=$row['order_retrieve_message']?></td>
		
	</tr>
<?php endforeach;?>
</tbody>
</table>
</div>



