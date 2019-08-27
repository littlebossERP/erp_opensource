<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile(\Yii::getAlias('@web').'/css/order/order.css');
$syncStatusMapping = [
''=>'--',
'0'=>TranslateHelper::t('等待同步'),
'1'=>TranslateHelper::t('同步中'),
'2'=>TranslateHelper::t('同步成功'),
'3'=>TranslateHelper::t('同步失败'),
'4'=>TranslateHelper::t('同步完成'),
];
$statusMapping = [
'--'=>'--',
'0'=>TranslateHelper::t('关闭'),
'1'=>TranslateHelper::t('开启'),
];
?>
<style>
.large-box  .modal-dialog{
	width: 1200px;
}

.large-box .modal-body{
	max-height: 800px;
	overflow-y: auto;	
}
</style>

<script type="text/javascript">

</script>

<div  class="tracking-index col2-layout">
<table class="table">
<thead>
	<tr>
		<th><?= TranslateHelper::t('账号')?></th>
		<th><?= TranslateHelper::t('账号简称')?></th>
		<th><?= TranslateHelper::t('开启状态')?></th>
		<th><?= TranslateHelper::t('同步状态')?></th>
		<th><?= TranslateHelper::t('最近同步时间')?></th>
		<th><?= TranslateHelper::t('错误信息')?></th>
	</tr>
</thead>
<tbody>
<?php if(!empty($sync_list)){ ?>
<?php foreach($sync_list as $account_name=>$row):?>
	<tr>
		<td><?=$account_name?></td>
		<td><?=empty($row['store_name'])?'--':$row['store_name']?></td>
		<td><?=empty($statusMapping[$row['is_active']])?$row['is_active']:$statusMapping[$row['is_active']] ?></td>
		<td><?=empty($syncStatusMapping[$row['status']])?$row['status']:$syncStatusMapping[$row['status']] ?></td>
		<td><?=empty($row['last_time'])?'--':date("Y-m-d H:i:s",$row['last_time'])?></td>
		<td><?=empty($row['message'])?'--':$row['message']?></td>
	</tr>
<?php endforeach;?>
<?php } ?>
</tbody>
</table>
</div>
</div>

