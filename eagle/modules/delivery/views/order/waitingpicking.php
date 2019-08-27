<?php
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\inventory\models\Warehouse;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
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
		<form id="form1" action="" method="post">
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
		</form>
				
		<form name="a" id="a" action="" method="post">
		<?php $divOrderInfoHtml = '<div class="sr-only">';?>
		<?=Html::hiddenInput('return_url',$return_url);?>
		<div  cellspacing="0" cellpadding="0" width="100%" class="table table-hover table-striped" >
			<?php if ($warehouseObj->is_oversea == 0){?>
			<?=Html::button('进行发货处理',['class'=>'btn btn-success','onclick'=>"javascript:mutidopicking('$showWarehouseId');"])?>
			<?php }?>
				<table cellspacing="0" cellpadding="0" width="100%" class="table table-hover table-striped" >
				<tr>
					<th><?=Html::checkbox('select_all','',['class'=>'select-all']);?></th>
					<th>平台订单号</th>
					<th>SKU</th>
					<th class="text-center text-nowrap">仓库</th>
					<th>拣货单号</th>
					<th>发货状态</th>
					<th class="text-center text-nowrap">操作</th>
				</tr>
				<?php if (count($orders)):foreach ($orders as $order):?>
				<!-- 一个订单begin -->
				<tr>
					<td><?=Html::checkbox('order_id[]','',['value'=>$order->order_id,'class'=>'order-id']);?></td>
					<td>
					<span class="order-info"><?=$order->order_id;?></span>
					<?php $divOrderInfoHtml.='<table id = "div_more_info_'.$order->order_id.'" style="text-align:left;width:100%"><tbody>';?>
		            <?php foreach ($order->items as $one){?>
		            <?php $divOrderInfoHtml.='<tr><td style="border:1px solid #d9effc;"><img alt="" src="'.$one->photo_primary.'" width="60px" height="60px"></td>'?>
		            <?php $divOrderInfoHtml.='<td style="border:1px solid #d9effc">SKU:'.$one->sku.'<br>'.$one->product_name.'</td>'?>
		            <?php $divOrderInfoHtml.='<td style="border:1px solid #d9effc;width:60px;" class="text-nowrap">'.$one->quantity.'</td>'?>
		            <?php $divOrderInfoHtml.='<td style="border:1px solid #d9effc;width:60px;" class="text-nowrap">'.$one->product_attributes.'</td></tr>'?>
		            <?php }?>
		            <?php $divOrderInfoHtml.='</tbody></table>'?>
					</td>
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
					<?=OdOrder::$showdeliveryStatus[$order->delivery_status]?>
					</td>
					<td>
						<a href="#" onclick="javascript:dopicking('<?=$order->order_id?>','<?=$showWarehouseId?>')">进行发货处理</a>|
						<a href="#" onclick="javascript:cancelpicking('<?=$order->order_id?>')">取消发货</a>
					</td>
				</tr>
				<?php endforeach;endif;?>
				</table>
		</div>
		<?=$divOrderInfoHtml.'</div>'?>
		</form>
		<?= \eagle\widgets\SizePager::widget(['pagination'=>$pagination , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
		<?= LinkPager::widget(['pagination' => $pagination]) ?>
				
				</li> 
				
			</ul>
		</div>
    </div>
  </div>

</div>

