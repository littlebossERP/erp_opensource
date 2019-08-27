<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\inventory\models\Warehouse;
use yii\caching\DummyCache;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/delivery/order/list.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/tracking/tracking.css");

$this->title = TranslateHelper::t('拣货中');
//$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<style>

.td_space_toggle{
	height: auto;
	padding: 0!important;
}
.table td,.table th{
	text-align: center;
}

table{
	font-size:12px;
}

.table>tbody>tr:nth-of-type(even){
	background-color:#f4f9fc
}
.table>tbody td{
color:#637c99;
}
.table>tbody a{
color:#337ab7;
}
.table>thead>tr>th {
height: 35px;
vertical-align: middle;
}
.table>tbody>tr>td {
height: 35px;
vertical-align: middle;
}
</style>
<div role="tabpanel">
<?php $warehouseObj = Warehouse::findOne($showWarehouseId);?>
  <!-- Tab panes -->
  <div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="home"><br>
    	<div class="inventory-index">
			<ul class="list-unstyled list-inline">
				<li class="">
					<?= $this->render('_leftmenu',['warehouse_id'=>$showWarehouseId,'warehouse'=>$warehouseObj])?>
				</li>
				<li class="content_right">
		<!-- Nav tabs -->
		  <?= $this->render('_topwarehouse',['warehouse_id'=>$showWarehouseId,'warehouses'=>$warehouses])?>
		<form id="form1" name="form1" action="" method="post">
			<div class="input-group">
		        <?php $sel = [
					'order_id'=>'平台订单号',
		        	'sku'=>'SKU',
		        	'srn'=>'SRN',
		        	'tracknum'=>'物流号',
		        	'buyerid'=>'买家账号',
		        	'email'=>'买家邮箱'
				]?>
				<?=Html::dropDownList('keys',@$_REQUEST['keys'],$sel,['class'=>'form-control input-sm','style'=>'width:120px;margin:0px'])?>
		      	<?=Html::textInput('searchval',@$_REQUEST['searchval'],['class'=>'form-control input-sm','id'=>'num','style'=>'width:120px'])?>
		      	<?=Html::submitButton('搜索',['class'=>"btn",'id'=>'search'])?>
		    </div>
			<?=Html::hiddenInput('warehouse_id',$showWarehouseId)?>
			<?=Html::hiddenInput('jianhuo_status',@$_REQUEST['jianhuo_status'],['id'=>'jianhuo_status'])?>
		</form>
				
		<form name="a" id="a" action="" method="post">
		<?=Html::hiddenInput('return_url',$return_url);?>
		<div class="panel panel-default" style="margin-top:5px;">
			<?=Html::button('拣货完成',['class'=>'btn btn-success','onclick'=>"javascript:mutidopickingorder()"])?>
				<table cellspacing="0" cellpadding="0" width="100%" class="table table-hover table-striped" >
				<tr>
					<th><?=Html::checkbox('select_all','',['class'=>'select-all']);?></th>
					<th>拣货单号</th>
					<th class="text-center text-nowrap">SKU总数</th>
					<th class="text-center text-nowrap">
						<?=Html::dropDownList('jianhuo_status',@$_REQUEST['jianhuo_status'],['0'=>'未拣货','1'=>'已拣货'],['prompt'=>'拣货状态','onchange'=>"dosearch('jianhuo',$(this).val());"])?>
					</th>
					<th class="text-center text-nowrap">操作</th>
				</tr>
				<?php if (count($orders)):foreach ($orders as $order):?>
				<tr>
					<td><?=Html::checkbox('order_id[]','',['value'=>$order->id,'class'=>'order-id']);?></td>
					<td><?=$order->deliveryid;?></td>
					<td><?=$order->skucount;?></td>
					<td><?=$order->jianhuo_status=='1'?'已拣货':'未拣货'?></td>
					<td>
						<a target="_blank" href="<?=Url::to(['/delivery/order/jianhuoview','id'=>$order->deliveryid])?>">查看</a>|
						<a target="_blank" href="<?=Url::to(['/delivery/order/jianhuoviewedit','id'=>$order->deliveryid,'view'=>0])?>">编辑</a>|
						<a href="#" onclick="javascript:dopickingorder('<?=$order->id?>')">拣货完成</a>|
						<a href="#" onclick="javascript:cancelpickingorder('<?=$order->id?>')">取消拣货</a>
					</td>
				</tr>
				<?php endforeach;endif;?>
				</table>
		</div>
		</form>
		<?= LinkPager::widget(['pagination' => $pagination]) ?>
				
				</li> 
				
			</ul>
		</div>
    </div>
  </div>

</div>
<script>
function dosearch(type,val){
	$('#'+type+'_status').val(val);
	document.form1.submit();
}
</script>
