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

</style>
<div role="tabpanel">
<?php $warehouseObj = Warehouse::findOne($showWarehouseId);?>
  <!-- Tab panes -->
  <div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="home"><br>
    	<div class="inventory-index">
			<ul class="list-unstyled list-inline">
				<li class="content_left">
					<?= $this->render('//layouts/menu_left_delivery',['warehouse_id'=>$showWarehouseId,'warehouse'=>$warehouseObj]) ?>
					<?= $this->render('_leftmenu',['warehouse_id'=>$showWarehouseId,'warehouse'=>$warehouseObj])?>
					</li> 
				<li class="content_right">
		<!-- Nav tabs -->
	  <ul class="nav nav-tabs" role="tablist">
	  <?php foreach ($warehouses as $warehouseId=>$warehouse){?>
	    <li role="presentation" <?php if ($warehouseId==$showWarehouseId){?>class="active"<?php }?>><a href="#home" aria-controls="home" role="tab" data-toggle="tab" class="warehouse" warehouse-id ="<?php echo $warehouseId?>"><?php echo $warehouse?></a></li>
	  <?php }?>
	  </ul>
		<form id="form1" action="" method="post">
			<div class="active">
			物流商<?=Html::dropDownList('default_carrier_code',@$data['default_carrier_code'],$carrier,['prompt'=>'','style'=>'width:100px;height:21px;'])?>
			运输服务<?=Html::dropDownList('default_shipping_method_code',@$data['default_shipping_method_code'],$shipping_services,['prompt'=>'','style'=>'width:100px;height:21px;'])?>
			账号<?=Html::dropDownList('selleruserid',@$data['selleruserid'],$selleruserids,['prompt'=>'','style'=>'width:100px;height:21px;'])?>
			<?php if ($warehouseObj->is_oversea == 0){?>
			拣货单<?=Html::dropDownList('is_print_picking',@$data['is_print_picking'],[0=>'未打印',1=>'已打印'],['prompt'=>'','style'=>'width:80px;height:21px;'])?>
			配货单<?=Html::dropDownList('is_print_distribution',@$data['is_print_distribution'],[0=>'未打印',1=>'已打印'],['prompt'=>'','style'=>'width:80px;height:21px;'])?>
			物流单<?=Html::dropDownList('is_print_carrier',@$data['is_print_carrier'],[0=>'未打印',1=>'已打印'],['prompt'=>'','style'=>'width:80px;height:21px;'])?>
			<?php }?>
			<?=Html::submitInput('搜索',['class'=>"btn btn-primary btn-xs",'id'=>'search'])?>
			<?=Html::hiddenInput('is_show',isset($data['is_show'])?$data['is_show']:0)?>
			<?=Html::hiddenInput('warehouse_id',$showWarehouseId)?>
			<?php if (@$data['is_show']==0){?>
			<?=Html::button('展开高级搜索',['class'=>"btn btn-primary btn-xs",'id'=>'show-search'])?>
			<?=Html::button('隐藏高级搜索',['class'=>"btn btn-primary btn-xs sr-only",'id'=>'hide-search'])?>
			<?php }else{?>
			<?=Html::button('展开高级搜索',['class'=>"btn btn-primary btn-xs sr-only",'id'=>'show-search'])?>
			<?=Html::button('隐藏高级搜索',['class'=>"btn btn-primary btn-xs",'id'=>'hide-search'])?>
			<?php }?>
			</div>
			<div id="more-search" class="panel panel-default <?php if (@$data['is_show']==0) echo 'sr-only';?>" style="margin-top:5px;">
				<table class="table table-condensed" style='font-family: 8pt;'>
				<tr>
					<th class="text-right" width="110px;">
					<?=Html::dropDownList('key',@$data['key'],$vals,['prompt'=>'','style'=>'width:100px;height:21px;'])?>
					</th>
					<td>
					<?=Html::input('','val',@$data['val'],['id'=>'num','style'=>'width:100px;height:21px;'])?>
				</td>
				</tr>
				</table>
			</div>
		</form>
				
		<form name="a" id="a" action="" method="post">
		<?=Html::hiddenInput('return_url',$return_url);?>
		<?=Html::hiddenInput('warehouse_id',$showWarehouseId)?>
			<?php $doarr=[
					''=>'批量操作',
					'confirmdelivery'=>'确认发货',
			];
			?>
			<?php $doCarrier=[
					''=>'物流操作',
					'getorderno'=>'上传订单到物流系统',
					'dodispatch'=>'交运订单',
					'gettrackingno'=>'获取物流号',
					'doprint'=>'打印物流单',
					'cancelorderno'=>'取消订单',
			];
			?>
			<?php $doarr3=[
					''=>'打印',
					'picking'=>'打印拣货单',
					'distribution'=>'打印配货单',
			];
			?>
		<div class="panel panel-default" style="margin-top:5px;">
			<div class="panel-heading">
			<?=Html::dropDownList('do','',$doarr,['style'=>'width:150px;','class'=>'do']);?> 
			<?=Html::dropDownList('do','',$doCarrier,['style'=>'width:150px;','class'=>'do-carrier']);?> 
			<?php if ($warehouseObj->is_oversea == 0){?>
			<?=Html::dropDownList('do','',$doarr3,['style'=>'width:150px;','class'=>'do_print']);?> 
			<?php }?>
			
			</div>
				<table class="table table-condensed table-bordered" style="font-size:8pt;">
				<tr class="success">
					<th><?=Html::checkbox('select_all','',['class'=>'select-all']);?>订单号</th>
					<th class="text-center text-nowrap">标签</th>
					<th class="text-center text-nowrap">物流商</th>
					<th class="text-center text-nowrap">运输服务</th>
					<th class="text-center text-nowrap">仓库</th>
					<th class="text-center text-nowrap">账号</th>
					<th class="text-center text-nowrap">平台状态</th>
					<th class="text-center text-nowrap">收货信息</th>
					<th class="text-center text-nowrap">物流号</th>
					<th class="text-center text-nowrap">操作</th>
				</tr>
				<tr class="success">
					<th colspan="1" class="text-center text-nowrap">商品图片</th>
					<th colspan="2" class="text-center text-nowrap">SKU</th>
					<th colspan="1" class="text-center text-nowrap">产品属性</th>
					<th colspan="1" class="text-center text-nowrap">数量</th>
					<th colspan="1" class="text-center text-nowrap">单价</th>
					<th colspan="4" class="text-center text-nowrap">商品信息</th>
					
				</tr>
				<?php foreach ($orders as $order){?>
				<!-- 一个订单begin -->
				<tr class="warning">
					<td><?=Html::checkbox('order_id[]','',['value'=>$order->order_id,'class'=>'order-id']);?><br/><?=$order->order_id;?></td>
					<td class="text-nowrap">
					<?php if ($order->exception_status>0&&$order->exception_status!='201'):?>
						<a class="label label-warning"><?=OdOrder::$exceptionstatus[$order->exception_status]?></a><br>
					<?php endif;?>
					<?php if (strlen($order->user_message)>0):?>
						<a class="label label-warning"><?=OdOrder::$exceptionstatus[OdOrder::EXCEP_HASMESSAGE]?></a><br>
					<?php endif;?>
					</td>
					<td class="text-nowrap"><?php echo isset($carrier[$order->default_carrier_code])?$carrier[$order->default_carrier_code]:'';?></td>
					<td class="text-nowrap"><?php echo isset($shipping_services[$order->default_shipping_method_code])?$shipping_services[$order->default_shipping_method_code]:'';?></td>
					<td class="text-nowrap"><?php echo $warehouses[$order->default_warehouse_id];?></td>
					<td class="text-nowrap"><?php echo $order->selleruserid;?></td>
					<td class="text-nowrap"><?php echo $order->order_source_status;?></td>
					<td class="text-nowrap">
					收件人 :<?php echo $order->consignee?><br>
					地址:<?php echo $order->consignee_address_line1." ".$order->consignee_address_line2." ".$order->consignee_address_line3?><br>
					  <?php echo $order->consignee_county." ".$order->consignee_district." ".$order->consignee_city." ".$order->consignee_province." ".$order->consignee_country."(".$order->consignee_country_code.")"?><br>
					邮编:<?php echo $order->consignee_postal_code?><br>
					电话:<?php echo $order->consignee_phone?><br>
					</td>
					<td>
					<?php if ($order->carrier_step == 1){?>
					已上传未交运<br>
					<?php }elseif ($order->carrier_step == 2){?>
					已交运<br>
					<?php }elseif ($order->carrier_step == 3){?>
					已获取物流号<br>
					<?php }elseif ($order->carrier_step == 4){?>
					已打印物流单<br>
					<?php }elseif ($order->carrier_step == 5){?>
					已取消<br>
					<?php }?>
					<?php if (count($order->trackinfos)):foreach ($order->trackinfos as $ot):?>
						<b><?=$ot->shipping_method_name?>:</b><a href="<?=$ot->tracking_link?>"><?=$ot->tracking_number?></a>
						<?php if ($ot->status==1):?>
							<font color="green">(√)</font>
						<?php else:?>
							<font color="red">(X)</font>
						<?php endif;?>
						<br>
					<?php endforeach;endif;?>
					</td>
					<td class="text-nowrap text-center">
					<a href="<?=Url::to(['/delivery/order/confirm-delivery','order_id'=>$order->order_id,'return_url'=>$return_url])?>" class="btn btn-success btn-xs"><?php echo TranslateHelper::t('确认发货');?></a><br>
					</td>
				</tr>
				<?php foreach ($order->items as $item){?>
				<tr class="warning">
					<td colspan="1" class="text-center"><img src="<?php echo $item->photo_primary?>" height='30px'></td>
					<td colspan="2" class="text-center text-nowrap"><?php echo $item->sku?></td>
					<td colspan="1" class="text-center"><?php echo $item->product_attributes?></td>
					<td colspan="1" class="text-center"><?php echo $item->ordered_quantity?></td>
					<td colspan="1" class="text-center"><?php echo $order->currency?> <?php echo $item->price?></td>
					<td colspan="4" class="text-center"><a href="#"><?php echo $item->product_name?></a></td>
				</tr>
				<?php }?>
				<tr class="success">
					<th colspan="1" class="text-right text-nowrap">
					付款备注
					</th>
					<td colspan="4"><?php echo $order->user_message?></td>
					<th colspan="5" class="text-right text-nowrap">
					下单时间：<?php echo date('Y-m-d H:i:s',$order->order_source_create_time)?> 
					产品总额：<?php echo $order->currency?> <?php echo $order->subtotal?>  
					运费总额：<?php echo $order->currency?> <?php echo $order->shipping_cost?> 
					订单总额：<?php echo $order->currency?> <?php echo $order->grand_total?>
					</th>
				</tr>
				<!-- 一个订单end -->
				<?php }?>
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

