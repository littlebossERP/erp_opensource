<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\carrier\helpers\CarrierHelper;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/carrier/carrierorder.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/delivery/order/list.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/tracking/tracking.css");
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
				<?=Html::hiddenInput('carrier_step',@$_REQUEST['carrier_step'],['id'=>'carrier_step'])?>
				<?=Html::dropDownList('keys',@$_REQUEST['keys'],$sel,['class'=>'form-control input-sm','style'=>'width:120px;margin:0px'])?>
		      	<?=Html::textInput('searchval',@$_REQUEST['searchval'],['class'=>'form-control input-sm','id'=>'num','style'=>'width:120px'])?>
		      	<?=Html::submitButton('搜索',['class'=>"btn",'id'=>'search'])?>
		    </div>
			<?=Html::hiddenInput('warehouse_id',$showWarehouseId)?>
		</form>
				
		<form name="a" id="a" action="" method="post">
		<?=Html::hiddenInput('return_url',$return_url);?>
		<div class="panel panel-default" style="margin-top:5px;">
			<?php if ($warehouseObj->is_oversea == 0){?>
			<button type="button" id="do-carrier" value='getorderno'  style="height: 28px;" class="btn btn-success btn-sm"><?= TranslateHelper::t('进行物流操作')?></button>
			<?php }?>
				<table  cellspacing="0" cellpadding="0" width="100%" class="table table-hover table-striped" >
				<tr>
					<th><?=Html::checkbox('select_all','',['class'=>'select-all']);?></th>
					<th>平台订单号</th>
					<th>SKU</th>
					<th class="text-center text-nowrap">仓库</th>
					<th>拣货单号</th>
					<th><?=Html::dropDownList('carrier_step',@$_REQUEST['carrier_step'],CarrierHelper::$carrier_step,['prompt'=>TranslateHelper::t('物流操作状态'),'style'=>'width:100px;','class'=>'search','onchange'=>"dosearch('carrier_step',$(this).val());"]);?></th>
					<th class="text-center text-nowrap">操作</th>
				</tr>
				<?php if (count($orders)):foreach ($orders as $order):?>
				<!-- 一个订单begin -->
				<tr>
					<td><?=Html::checkbox('order_id[]','',['value'=>$order->order_id,'class'=>'order-id']);?></td>
					<td><?=$order->order_id;?></td>
					<td>
						<?php if (count($order->items)):foreach ($order->items as $item):?>
						<?php if (isset($item->sku)&&strlen($item->sku)):?>
						<?=$item->sku?>&nbsp;<b>X<?=$item->quantity?></b><br>
						<?php endif;?>
						<?php endforeach;endif;?>
					</td>
					<td>
					<?php if ($order->default_warehouse_id>=0&&count($warehouses)){echo $warehouses[$order->default_warehouse_id];}?>
					</td>
					<td>
						<?=$order->delivery_id?>
					</td>
					<td>
					<?=CarrierHelper::$carrier_step[$order->carrier_step] ?>
		            <?php ?>
					</td>
					<td>
						<a href="<?=Url::to(['/carrier/carrieroperate/getorderno','order_id'=>$order->order_id])?>" target="_blank"><?php echo TranslateHelper::t('进行物流操作');?></a>|
						<a href="#" onclick="javascript:cancelpicking('<?=$order->order_id?>')">取消发货</a>
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
	$('#'+type).val(val);
	document.form1.submit();
}
</script>
