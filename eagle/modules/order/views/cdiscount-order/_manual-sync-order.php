<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile(\Yii::getAlias('@web').'/css/order/order.css');

//$this->registerJs("cdiscountOrder.ajaxManualSyncInfo();" , \yii\web\View::POS_READY);

$sync_type=[
	'N'=>'初始化同步',//FetchNewAccountOrder
	'R'=>'自动同步',//FetchRecentOrder
	'M'=>'手工同步',//ManualSyncOrder
];
$sync_status=[
	'R'=>'运行中',//running
	'C'=>'同步完成',//completed
	'F'=>'同步出错',//failed
];

?>
<style>

.manual-sync-order-win .modal-dialog{
	min-width: 800px;
	max-width: 80%;
	max-height: 80%;
	overflow: auto;	
}
#sync_order_account_list_tb th,#sync_order_account_list_tb td{
	white-space: normal!important;
	padding:4px!important;
	vertical-align: middle!important;
}


</style>

<script type="text/javascript">

</script>


<div>
	<div><button class="btn-xs" type="button" onclick="cdiscountOrder.ajaxManualSyncInfo()">刷新所有店铺同步信息</button></div>
<?php if(!empty($sync_job_info)){ ?>
	<table class="table" id="sync_order_account_list_tb">
		<tr>
			<th>账号</th>
			<th>最近同步类型</th>
			<th>同步状态</th>
			<th style="display:none">错误信息</th>
			<th>运行时间</th>
			<th>拉取的订单时间段</th>
			<th>该次同步到的订单数</th>
			<th>手工同步</th>
		</tr>
	<?php foreach($sync_job_info as $site_id=>$info){ ?>	
	
		<tr>
			<td><?=$info['username']?></td>
			<td id="sync_type-<?=$site_id?>"><?=empty($sync_type[$info['sync_type']])? $info['sync_type'] : $sync_type[$info['sync_type']] ?></td>
			<td id="sync_status-<?=$site_id?>"><?=empty($sync_status[$info['sync_status']])? '' : $sync_status[$info['sync_status']] ?></td>
			<td id="error_log-<?=$site_id?>" style="display:none"><?=empty($info['sync_info']['error_log'])? '' : $info['sync_info']['error_log'] ?></td>
			<td>开始：<span id="start_time-<?=$site_id?>"><?=empty($info['sync_info']['start_time'])? '--' : $info['sync_info']['start_time'] ?></span><br>
				完成：<span id="end_time-<?=$site_id?>"><?=empty($info['sync_info']['end_time'])? '--' : $info['sync_info']['end_time'] ?></span>
			</td>
			<td>由：<span id="begincreationdate-<?=$site_id?>"><?=empty($info['sync_info']['begincreationdate'])? '--' : $info['sync_info']['begincreationdate'] ?></span><br>
				至：<span id="endcreationdate-<?=$site_id?>"><?=empty($info['sync_info']['endcreationdate'])? '--' : $info['sync_info']['endcreationdate'] ?></span>
			</td>
			<td id="order_count-<?=$site_id?>"><?=empty($info['sync_info']['order_count'])? '--' : $info['sync_info']['order_count'] ?></td>
			<td><div id="sync_btn-<?=$site_id?>" style="display:<?=(empty($info['sync_status']) || $info['sync_status']=='C' || $info['sync_status']=='F')?'block':'none' ?>">
				平台订单时间：<input id="sync_order_time-<?=$site_id?>" class="iv-input" type="date" value="<?=date("Y-m-d",time()-3600*24) ?>"><br>
				<button class="btn-xs" type="button" onclick="cdiscountOrder.ManualSyncStoreOrder(<?=$site_id?>)">立即手工同步</button></div>
			</td>
		</tr>
	<?php } ?>
<?php }else{ echo "<span>没有有效账号可以手工同步</span>"; } ?>
</div>

