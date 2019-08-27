<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\helpers\AmazonOrderHelper;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/order/amazonOrder/order_sync.js?v=".AmazonOrderHelper::$amzapiVer, ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile(\Yii::getAlias('@web').'/css/order/order.css');

$statusMapping = [
''=>TranslateHelper::t('全部'),
'0'=>TranslateHelper::t('等待同步'),
'1'=>TranslateHelper::t('同步中'),
'2'=>TranslateHelper::t('同步成功'),
'3'=>TranslateHelper::t('同步失败'),
'4'=>TranslateHelper::t('同步完成'),
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

<script type="text/javascript">
<!--

//-->
//显示手动同步页
function showTable(obj){
	$('#myMessage').modal('show');
	$('[name=selleruserid]').val($(obj).data('account-name'));
	$('[name=startdate]').val($(obj).data('start-time'));
	$('[name=enddate]').val($(obj).data('end-time'));
}

//同步AJAX
function manualSync(sellerloginid,startdate,enddate,synctype){
	$.showLoading();
	$.post('<?=Url::to(['/order/aliexpressorder/monual-sync'])?>',{sellerloginid:sellerloginid,startdate:startdate,enddate:enddate,synctype:synctype},function(result){
		$.hideLoading();
		if(result.msg == 'Synchronous success') {
			//成功
			bootbox.alert('同步成功');
			document.location.reload();
		}else if(result.msg == 'Today has been synchronized!') {
			//每天手动同步次数：一次
			bootbox.alert('已同步，请稍后再次操作');
			document.location.reload();
		}else {
			//失败
			bootbox.alert('同步异常'+result.msg);
		}

	}, 'json');
}
</script>

<div  class="tracking-index col2-layout">
<?=$this->render('_leftmenu',['counter'=>$counter]);?>
<div class="content-wrapper">
<!-- oms 2.1 nav start  -->
	<?= $order_nav_html;?>
<!-- oms 2.1 nav end  -->

<!-- <form class="form-inline" action=""> -->
<!-- <div style="height: 30px;"></div> -->
<!-- <?= TranslateHelper::t('同步状态')?> -->
<!-- <?=Html::dropDownList('sync_status',@$_REQUEST['sync_status'],$statusMapping,['class'=>'iv-input','style'=>'width:150px;margin:0px','id'=>'sync_status']);?> -->
<!-- <?= TranslateHelper::t('最近同步时间小于')?> -->
<!-- <?=Html::input('date','last_sync_time',@$_REQUEST['last_sync_time'],['class'=>'iv-input','style'=>'width:130px','id'=>'last_sync_time'])?>&nbsp;&nbsp; -->
<!-- <?=Html::submitButton('搜索',['class'=>"iv-btn btn-search",'id'=>'search'])?>&nbsp;&nbsp; -->
<!-- <?=Html::Button('查询同步订单详情',['class'=>"iv-btn btn-important",'id'=>'search_order_detail' ,'onclick'=>"OrderSync.ShowSearchBox()"])?> -->
<!-- </form> -->
<div style="height: 30px;"></div>
<table class="table">
<thead>
	<tr>
		<th><?= TranslateHelper::t('账号')?></th>
		<th><?= TranslateHelper::t('店名')?></th>
		<th><?= TranslateHelper::t('站点')?></th>
		<th><?= TranslateHelper::t('同步状态')?></th>
		<!-- <th><?= TranslateHelper::t('订单产生时间段')?></th> -->
		<th><?= TranslateHelper::t('最近同步时间')?></th>
		<!-- <th><?= TranslateHelper::t('订单总数')?></th> -->
		<th><?= TranslateHelper::t('错误信息')?></th>
		<!--  <th><?= TranslateHelper::t('操作')?></th>-->
	</tr>
</thead>
<tbody>
<?php foreach($sync_list as $account_name=>$list_val):?>
	<?php foreach($list_val as $list_val_raw):?>
		<tr>
			<td>
				<?= $list_val_raw['merchant_id'] ?>
			</td>
			<td>
				<!-- <?= $account_name?> -->
				<!-- <?= $list_val_raw['merchant_id'] ?> -->
				<?php echo $merchStoreMap[$list_val_raw['merchant_id']]?>
			</td>
			<td>
				<?php 
				$enNameShort = AmazonApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG[$list_val_raw['marketplace_id']];
				echo $sysCountry[$enNameShort]['country_zh']." (".$enNameShort.")" ;
				?>
			</td>
			<td>
				<?= ($list_val_raw['process_status']=='0')?'未同步':
					(($list_val_raw['process_status']=='1')?'同步中':
					(($list_val_raw['process_status']=='2')?'同步完成':''))?>
			</td>
			<td>
				<?= date('y-m-d H:i:s',$list_val_raw['last_finish_time'])?>
			</td>
			<td>
				<?= $list_val_raw['error_message']?>
			</td>
		</tr>
		<!-- <?= var_dump($list_val_raw)?> -->
	<?php endforeach;?>
<?php endforeach;?>
<!--  <?= var_dump($sync_list)?> -->
</tbody>
</table>
</div>
</div>



