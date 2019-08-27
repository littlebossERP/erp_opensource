<?php
use eagle\modules\util\helpers\TranslateHelper;

//$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/CdiscountAccountsNewOrEdit.js", ['depends' => ['yii\web\JqueryAsset']]);
//$this->registerJs("platform.CdiscountAccountsNewOrEdit.initBtn();" , \yii\web\View::POS_READY);
?>
<style>
#sync-info-table th,#sync-info-table td{
	border:1px solid gray;
}
#sync-info-table th{
	background-color:#C7C7C7;
}
h1{
	font-family: 'Arial Negreta', 'Arial';
    font-weight: 700;
    font-style: normal;
    font-size: 24px;
    color: #333333;
    text-align: left;
    line-height: normal;
    margin-bottom: 10px;
    border-bottom: solid 1px;
    padding-bottom: 10px;
}
</style>

<div style="padding:10px 15px 0px 15px">
	<h1><?= @$_GET['store_name']?></h1>
	<table style="font-size: 14px;"  class="table table-hover">
		<tr>
			<th style="width:120px;text-align:center;"><?= TranslateHelper::t('同步内容') ?></th>
			<th style="width:120px;text-align:center;"><?= TranslateHelper::t('系统同步') ?></th>
			<th style="width:150px;text-align:center;"><?= TranslateHelper::t('同步状态(失败次数)') ?></th>
			<th style="width:150px;text-align:center;"><?= TranslateHelper::t('最近同步时间') ?></th>
			<!-- <th style="width:120px;text-align:center;"><?= TranslateHelper::t('上次同步结果') ?></th> -->
			<th style="width:200px;text-align:center;"><?= TranslateHelper::t('错误信息') ?></th>
			<th style="width:150px;text-align:center;"><?= TranslateHelper::t('操作') ?></th>
		</tr>
		<?php foreach ($syncInfo['orderSyncInfo'] as $key=>$row):?>
		<tr>
			<td style="text-align:center;"><?= ((in_array($platform, ['amazon']))?$key:"").TranslateHelper::t('同步订单') ?></td>
			<td style="text-align:center;"><?=(isset($row['is_active']) && ((int)$row['is_active']==1 || (string)$row['is_active']=='Y'))?'已开启':'已关闭' ?></td>
			<td style="text-align:center;"><?=isset($row['status'])?$row['status']:'' ?> <?=isset($row['times'])?"(".$row['times'].")":'' ?></td>
			<td style="text-align:center;"><?=isset($row['last_time'])?$row['last_time']:'' ?></td>
			<!--<td style="text-align:center;"><?= TranslateHelper::t('失败') ?></td> -->
			<td style="text-align:center;"><?=isset($row['message'])?$row['message']:'' ?></td>
			<td style="text-align:center;">
				<!--
				<?php if(isset($row['is_active']) && ((int)$row['is_active']==1) || (string)$row['is_active']=='Y'):?>
				<a style="text-decoration: none;" href="javascript:void(0)" onclick="setSyncByType(0,'<?=$platform ?>','<?=$site_id ?>','order')"><?= TranslateHelper::t('关闭') ?>|</a>
				<?php else:?>
				<a style="text-decoration: none;" href="javascript:void(0)" onclick="setSyncByType(1,'<?=$platform ?>','<?=$site_id ?>','order')"><?= TranslateHelper::t('开启') ?>|</a>
				<?php endif;?>
				-->
				<?php if (in_array($platform, ['aliexpress']) && isset($row['times']) && $row['times'] >=10):?>
				<a style="text-decoration: none;" href="javascript:void(0)" onclick="setSyncByType(1,'<?=$platform ?>','<?=$site_id ?>','order')"><?= TranslateHelper::t('重新同步') ?></a> 
				<?php endif;?>
			</td>
		</tr>
		<?php endforeach;?>
	
		<?php if (in_array($_GET['platform'], ['aliexpress' , 'ebay' , 'dhgate' , 'wish'])):?>
		<tr class="striped-row">
			<td style="text-align:center;"><?=TranslateHelper::t('同步站内信') ?></td>
			<td style="text-align:center;"><?=(isset($syncInfo['messageSyncInfo']['is_active']) && ((int)$syncInfo['messageSyncInfo']['is_active']==1 || $syncInfo['messageSyncInfo']['is_active']=='Y'))?'已开启':'已关闭' ?></td>
			<td style="text-align:center;"><?=isset($syncInfo['messageSyncInfo']['status'])?$syncInfo['messageSyncInfo']['status']:'' ?></td>
			<td style="text-align:center;"><?=isset($syncInfo['messageSyncInfo']['last_time'])?$syncInfo['messageSyncInfo']['last_time']:'' ?></td>
			<!--<td style="text-align:center;"><?= TranslateHelper::t('失败') ?></td> -->
			<td style="text-align:center;"><?=isset($syncInfo['messageSyncInfo']['message'])?$syncInfo['messageSyncInfo']['message']:'' ?></td>
			<td style="text-align:center;">
				<!--
				<?php if(isset($syncInfo['messageSyncInfo']['is_active']) && ((int)$syncInfo['messageSyncInfo']['is_active']==1 || (string)$syncInfo['messageSyncInfo']['is_active']=='Y')):?>
				<a style="text-decoration: none;" href="javascript:void(0)" onclick="setSyncByType(0,'<?=$platform ?>','<?=$site_id ?>','message')"><?= TranslateHelper::t('关闭') ?>|</a>
				<?php else:?>
				<a style="text-decoration: none;" href="javascript:void(0)" onclick="setSyncByType(1,'<?=$platform ?>','<?=$site_id ?>','message')"><?= TranslateHelper::t('开启') ?>|</a>
				<?php endif;?>
				 <a style="text-decoration: none;" href="javascript:void(0)" onclick=""><?= TranslateHelper::t('重新同步') ?></a> -->
			</td>
		</tr>
		<?php endif;?>
		
		<?php if (in_array($_GET['platform'], ['ebay','wish','ensogo','lazada','jumia','linio'])):?>
		<tr>
			<td style="text-align:center;"><?= TranslateHelper::t('同步在线商品') ?></td>
			<td style="text-align:center;"><?=(isset($syncInfo['productSyncInfo']['is_active']) && ((int)$syncInfo['productSyncInfo']['is_active']==1 || (string)$syncInfo['productSyncInfo']['is_active']=='Y'))?'已开启':'已关闭' ?></td>
			<td style="text-align:center;"><?=isset($syncInfo['productSyncInfo']['status'])?$syncInfo['productSyncInfo']['status']:'' ?></td>
			<td style="text-align:center;"><?=isset($syncInfo['productSyncInfo']['last_time'])?$syncInfo['productSyncInfo']['last_time']:'' ?></td>
			<!--<td style="text-align:center;"><?= TranslateHelper::t('失败') ?></td> -->
			<td style="text-align:center;"><?=isset($syncInfo['productSyncInfo']['message'])?$syncInfo['productSyncInfo']['message']:'' ?></td>
			<td style="text-align:center;">
				<!--
				<?php if(isset($syncInfo['productSyncInfo']['is_active']) && ((int)$syncInfo['productSyncInfo']['is_active']==1 || (string)$syncInfo['productSyncInfo']['is_active']=='Y')):?>
				<a style="text-decoration: none;" href="javascript:void(0)" onclick="setSyncByType(0,'<?=$platform ?>','<?=$site_id ?>','product')"><?= TranslateHelper::t('关闭') ?>|</a>
				<?php else:?>
				<a style="text-decoration: none;" href="javascript:void(0)" onclick="setSyncByType(1,'<?=$platform ?>','<?=$site_id ?>','product')"><?= TranslateHelper::t('开启') ?>|</a>
				<?php endif;?>
				<a style="text-decoration: none;" href="javascript:void(0)" onclick=""><?= TranslateHelper::t('重新同步') ?></a> -->
			</td>
		</tr>
		<?php endif;?>
	</table>
</div>
<script type="text/javascript">
	function setSyncByType(status,platform,site,type){
		$.showLoading();
		$.get( global.baseUrl+'platform/platform/set-sync-by-type?platform='+platform+'&site_id='+site+'&type='+type+'&status='+status,
			function (data){
				$.hideLoading();
				bootbox.alert(data);
			}
		);
	}
</script>