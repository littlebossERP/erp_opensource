<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile(\Yii::getAlias('@web').'/css/order/order.css');

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


<table class="table">
<thead>
	<tr>
		<th><?= TranslateHelper::t('账号简称')?></th>
		<th><?= TranslateHelper::t('开启状态')?></th>
		<th><?= TranslateHelper::t('同步状态')?></th>
		<th><?= TranslateHelper::t('最近同步时间')?></th>
		<th><?= TranslateHelper::t('错误信息')?></th>
	</tr>
</thead>
<tbody>
<?php if(!empty($sync_list)){ ?>
<?php foreach($sync_list as $site_id=>$row):?>
	<tr>
		<td><?=empty($row['store_name'])?'--':$row['store_name']?></td>
		<td><?=!empty($row['is_active'])?$row['is_active']:'--' ?></td>
		<td><?=!empty($row['status'])?$row['status']:'--' ?></td>
		<td><?=empty($row['last_time'])?'--':$row['last_time'] ?></td>
		<td><?=empty($row['message'])?'--':$row['message']?></td>
	</tr>
<?php endforeach;?>
<?php } ?>
</tbody>
</table>


