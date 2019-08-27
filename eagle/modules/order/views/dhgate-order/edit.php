<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrderShipped;
use eagle\models\QueueDhgateGetorder;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/lib/ystep/ystep.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerCssFile(\Yii::getAlias('@web')."/js/lib/ystep/ystep.css", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/aliexpressOrder/edit.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->title='敦煌订单编辑';
$this->params['breadcrumbs'][] = $this->title;

//修改订单 发货中 只能修改 收件人信息 和 运费
if ($order->order_status == OdOrder::STATUS_PAY){
	$input_attr = [];
	$select_attr = [];
}else{
	$input_attr = ['readonly'=>"readonly"];
	$select_attr = ['disabled'=>"disabled"];
}
?>
<style>
/* .table td,.table th {
	text-align: center;
} */

table {
	font-size: 12px;
}

.table>tbody td {
	color: #637c99;
}

.table>tbody a {
	color: #337ab7;
}

.table>thead>tr>th {
	height: 35px;
	vertical-align: middle;
}

.table>tbody>tr>td {
	height: 35px;
	vertical-align: middle;
}
.text-right{
	font-weight:bold;
}
.text-left{
	font-weight:bold;
}
</style>
<form action="" method="post">
	<?=Html::input('hidden','orderid',$order->order_id)?>
	<?=Html::input('hidden','statushide',$order->order_status,['id'=>'statushide'])?>
	<div class="panel panel-info">
	<div class="panel-body">
		<div class="ystep1" style="text-align:center"></div>
		<ul class="list-inline">
		  <li><strong style="color: orange;">小老板订单号:</strong><b style="color:#637c99;"><?=$order->order_id?></b></li>
		  <li><strong style="color: orange;">卖家账号:</strong><b style="color:#637c99;"><?=$order->selleruserid?></b></li>
		  <li><strong style="color: orange;">敦煌订单号:</strong><b style="color:#637c99;"><?=$order->order_source_order_id?></b></li>
		  <li><strong style="color: orange;">敦煌状态:</strong><b style="color:green;"><?= empty($order->order_source_status)?"":$order->order_source_status."(".QueueDhgateGetorder::$orderStatus[$order->order_source_status].")";?></b></li>
		  <li><strong style="color: orange;">买家账号:</strong><b style="color:#637c99;"><?=$order->source_buyer_user_id?></b></li>
		</ul>
	</div>
	<table class="table table-bordered" style="font-size:12px;border:0px;">
		<tr><td class="text-right" style="vertical-align:middle;" width="120px;">订单总金额</td><td class="text-left"><?=Html::textInput('grand_total',$order->grand_total)?>&nbsp;<?=$order->currency?></td></tr>
		<tr><td class="text-right">物流费用</td><td class="text-left"><?=Html::textInput('shipping_cost',$order->shipping_cost)?>&nbsp;<?=$order->currency?></td></tr>
		<tr><td class="text-right">运输服务</td><td class="text-left"><?=Html::dropDownList('default_shipping_method_code',$order->default_shipping_method_code,$shipmethodList ,$select_attr+=['prompt'=>'运输服务'])?></td></tr>
		<tr><td class="text-right">匹配仓库</td><td class="text-left"><?=Html::dropDownList('default_warehouse_id',$order->default_warehouse_id,$warehouseList,$select_attr)?></td>
		<tr><td class="text-right">订单状态</td><td class="text-left"><?=OdOrder::$status[$order->order_status]?></td></tr>
	</table>
	</div>
<ul id="myTab" class="nav nav-tabs" style="height: 41px;">
<?php $arr = array('info'=>'订单详情','carrier'=>'物流信息','time'=>'时间记录')?>
<?php foreach ($arr as $key=>$value){?>
   <li class="<?php echo $key;?>"><a href="<?php echo '#'.$key;?>" data-toggle="tab"><?php echo $value?></a></li>
<?php }?>
</ul>
<div id="myTabContent" class="tab-content">
<div class='info tab-pane fade' id="info"  style="padding-top:8px;">	
	<p><strong style="color: orange;">付款备注：<?= $order->user_message?></strong></p>
	<table class="table table-bordered" style="font-size:12px;border:0px;">
	<thead>
	<tr>
		<th></th>
		<th>收件人</th>
		<th>国家</th>
		<th>州</th>
		<th>城市</th>
		<th>邮编</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td><b>收件人信息</b></td>
		<td><?=Html::textInput('consignee',$order->consignee)?></td>
		<td><?=Html::textInput('consignee_country',$order->consignee_country,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_province',$order->consignee_province,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_city',$order->consignee_city,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_postal_code',$order->consignee_postal_code,['style'=>'width:200px'])?></td>
	</tr>
	<tr>
		<th></th>
		<th>手机</th>
		<th>电话</th>
		<th>地址1</th>
		<th>地址2</th>
		<th>地址3</th>
	</tr>
	<tr>
		<td><b>收件人信息</b></td>
		<td><?=Html::textInput('consignee_mobile',$order->consignee_mobile,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_phone',$order->consignee_phone,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_address_line1',$order->consignee_address_line1,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_address_line2',$order->consignee_address_line2,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_address_line3',$order->consignee_address_line3,['style'=>'width:200px'])?></td>
	</tr>
	</tbody>
	</table>
	<table class="table table-bordered" id="TableTransactionModify" style="font-size:12px;border:0px;">
	<tr>
		<th>标题</th>
		<th>SKU</th>
		<th>数量</th>
		<th>价格</th>
		<th><?=Html::button('添加商品',['onclick'=>"TableTransactionModifyAdd()"]);?>
		<?php if (count($order->items)>1):?>
			<?=Html::button('拆分订单',['onclick'=>"window.location.href='".Url::to(['/order/order/splitorder','orderid'=>$order->order_id])."'"]);?>
		<?php endif;?></th>
	</tr>
	<?php if (count($order->items)):foreach($order->items as $item):?>
	<tr>
		<td><?=Html::textInput('item[product_name][]',$item->product_name)?></td>
		<td><?=Html::textInput('item[sku][]',$item->sku)?></td>
		<td><?=Html::textInput('item[quantity][]',$item->quantity)?></td>
		<td><?=Html::textInput('item[price][]',$item->price)?></td>
		<td><?=Html::input('hidden','item[itemid][]',$item->order_item_id)?></td>
	</tr>
	<?php endforeach;endif;?>
	</table>
</div>
<div class='carrier tab-pane fade' id="carrier"  style="padding-top:8px;">	
	<table class="table table-bordered" style="font-size:12px;border:0px;">
	<thead>
	<tr>
		<th width="200px;">物流方式</th>
		<th width="120px;">物流号</th>
		<th width="100px;">标记状态</th>
		<th>备注</th>
		<th>错误</th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($order->trackinfos as $one){?>
	<tr>
		<td><?php echo $one->shipping_method_name?></td>
		<td><b style="color:green;"><?php echo $one->tracking_number?></b></td>
		<td><?php echo OdOrderShipped::$status[$one->status]?></td>
		<td><b style="color:orange;"><?php echo $one->description?></b></td>
		<td><?php echo $one->errors?></td>
	</tr>
	<?php }?>
	</tbody>
	</table>
</div>
<div class='time tab-pane fade' id="time"  style="padding-top:8px;">
<?php if ($order->delivery_time>0){?>	
<p><strong style="color: orange;">发货时间:</strong><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->delivery_time);?></b></p>
<?php }?>
<?php if ($order->paid_time>0){?>	
<p><strong style="color: orange;">付款时间:</strong><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->paid_time);?></b></p>
<?php }?>
<?php if ($order->order_source_create_time>0){?>	
<p><strong style="color: orange;">下单时间:</strong><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->order_source_create_time);?></b></p>
<?php }?>
</div>
</div>
<hr>
<center><?=Html::submitButton('保存',['class'=>'btn btn-success']);?>&nbsp;&nbsp;&nbsp;
<?=Html::submitButton('取消',['class'=>'btn btn-default','onclick'=>"window.close();"]);?></center>
</form>
<table>
<tr id="new" style="display:none">
		<td><?=Html::textInput('item[product_name][]')?></td>
		<td><?=Html::textInput('item[sku][]')?></td>
		<td><?=Html::textInput('item[ordered_quantity][]')?></td>
		<td><?=Html::textInput('item[price][]')?></td>
		<td>
			<?=Html::input('hidden','item[itemid][]')?>
			<?=Html::button('删除',['onclick'=>"removeTransaction(this)"])?>
		</td>
	</tr>
	</table>
<script>
function TableTransactionModifyAdd(){
	$('#TableTransactionModify').append($('#new').clone().show());
}
function  removeTransaction(obj){
	$(obj).parent().parent().remove();
}
</script>