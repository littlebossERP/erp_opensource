<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/order/aliexpressOrder/order_sync.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
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


<div class="content-wrapper">

<form class="form-inline" action="" style="display: none;">
<div style="height: 30px;display: none;"></div>
<?= TranslateHelper::t('同步状态')?>
<?=Html::dropDownList('sync_status',@$_REQUEST['sync_status'],$statusMapping,['class'=>'iv-input','style'=>'width:150px;margin:0px','id'=>'sync_status']);?>
<?= TranslateHelper::t('最近同步时间小于')?>
<?=Html::input('date','last_sync_time',@$_REQUEST['last_sync_time'],['class'=>'iv-input','style'=>'width:130px','id'=>'last_sync_time'])?>&nbsp;&nbsp;
<?=Html::submitButton('搜索',['class'=>"iv-btn btn-search",'id'=>'search'])?>&nbsp;&nbsp;
<?=Html::Button('查询同步订单详情',['class'=>"iv-btn btn-important",'id'=>'search_order_detail' ,'onclick'=>"OrderSync.ShowSearchBox()"])?>
</form>

<table class="table">
<thead>
	<tr>
		<th><?= TranslateHelper::t('账号')?></th>
		<th><?= TranslateHelper::t('账号简称')?></th>
		<th><?= TranslateHelper::t('同步状态（失败次数）')?></th>
		<th><?= TranslateHelper::t('订单产生时间段')?></th>
		<th><?= TranslateHelper::t('最近同步时间')?></th>
		<th><?= TranslateHelper::t('订单总数')?></th>
		<th><?= TranslateHelper::t('错误信息')?></th>
		<!--  <th><?= TranslateHelper::t('操作')?></th>-->
	</tr>
</thead>
<tbody>
<?php foreach($sync_list as $account_name=>$row):?>
	<tr>
		<td><?=$account_name?></td>
		<td><?=''?></td>
		<td><?=empty($statusMapping[$row['status']])?$row['status']:$statusMapping[$row['status']]."(".(empty($row['times'])?"0":$row['times']).")"?></td>
		<td><?=(($row['start_time']>0)?date('y-m-d H:i:s',$row['start_time']):'')."~".(($row['end_time']>0)?date('y-m-d H:i:s',$row['end_time']):'');?></td>
		
		<td><?=$row['last_time']>0?date('y-m-d H:i:s',$row['last_time']):''?></td>
		<td><?=empty($row['order_item'])?'0':$row['order_item']?></td>
		<td><?=$row['message']?></td>
		
		<!--  <td><a href="#" onclick="showTable(this);"  data-account-name="<?=$account_name?>" data-start-time="<?=(($row['end_time']>0)?date('Y-m-d',$row['end_time']):date('Y-m-d'))?>" data-end-time=<?=date('Y-m-d') ?>><?=TranslateHelper::t('手动同步')?></a></td>-->
	</tr>
<?php endforeach;?>
</tbody>
</table>
</div>

<div class="modal-footer">
	<button type="button" class="btn btn-default" id="close">关闭</button>
</div>

<!-- Modal 手动同步订单modal-->
<div class="modal fade" id="myMessage" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<form  enctype="multipart/form-data"?>">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">
						&times;
					</button>
					<h4 class="modal-title" id="myModalLabel">
						同步订单
					</h4>
				</div>
				<div class="modal-body">
					<?php
					$dotype=[
						'MO_CHOSE'=>'同步类型',
						'PLACE_ORDER_SUCCESS'=>'等待买家付款',
						'IN_CANCEL'=>'买家申请取消',
						'WAIT_SELLER_SEND_GOODS'=>'等待您发货',
						'SELLER_PART_SEND_GOODS'=>'部分发货',
						'WAIT_BUYER_ACCEPT_GOODS'=>'等待买家收货',
						'FUND_PROCESSING'=>'等待退放款处理',
						'FINISH'=>'已结束的订单',
						'IN_ISSUE'=>'含纠纷的订单',
						'IN_FROZEN'=>'冻结中的订单',
						'WAIT_SELLER_EXAMINE_MONEY'=>'等待您确认金额',
						'RISK_CONTROL'=>'风控中订单'
					];
					?>
					卖家账户:<?=Html::dropDownList('selleruserid',@$_REQUEST['selleruserid'],$selleruserids,['class'=>'form-control input-sm','id'=>'sellerloginid','style'=>'width:150px;margin:0px','prompt'=>'卖家账号'])?><br>
					选择同步时间:<br>
					开始时间<br><?=Html::input('date','startdate',@$_REQUEST['startdate'],['class'=>'form-control','style'=>'width:130px','id'=>'startdate'])?>
					结束时间<br><?=Html::input('date','enddate',@$_REQUEST['enddate'],['class'=>'form-control','style'=>'width:130px;margin:0px','id'=>'enddate'])?>
					同步类型:<br><?=Html::dropDownList('do','s',$dotype,['class'=>'form-control input-sm do','style'=>'width:150px;margin:0px','id'=>'synctype']);?>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
					<button type="button" class="btn btn-primary" onclick="manualSync($('#sellerloginid').val(),$('#startdate').val(),$('#enddate').val(),$('#synctype').val())"> 提交</button>
				</div>
			</div>
		</div>
	</form>
</div>

