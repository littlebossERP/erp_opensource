<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/lib/ystep/ystep.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerCssFile(\Yii::getAlias('@web')."/js/lib/ystep/ystep.css", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderJumiaEdit.js?v=1", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderEdit.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->title='Jumia订单编辑';
$this->params['breadcrumbs'][] = $this->title;
?>
<form action="" method="post" class="col-xs-12">
<div>
	<?=Html::input('hidden','orderid',$order->order_id)?>
	<?=Html::input('hidden','statushide',$order->order_status,['id'=>'statushide'])?>
	<br>
	<div>
		<font color="#8b8b8b" font-size="12px">订单号:<b><?=$order->order_id?></b>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		下单日期:<b><?=$order->order_source_create_time>0?date('Y-m-d H:i:s',$order->order_source_create_time):''?></b>
		</font>
	</div>
	<!-- 基本信息 -->
	<h5 style="margin:8px 0">基本信息</h5>
	<div style="background-color: #f4f9fc">
	<div class="ystep1" style="text-align:center"></div>
	<br>
	<table class="table table-bordered" style="font-size:12px;border:0px;">
	<tr>
		<td>买家账号</td>
		<td>订单总金额</td>
		<td>订单状态</td>
		<td>匹配物流</td>
		<td>订单费用</td>
		<td>匹配仓库</td>
	</tr>
	<tr style="background-color: #fff">
		<td style="border:1px solid #ddd"><?=$order->source_buyer_user_id?></td>
		<td style="border:1px solid #ddd"><?=Html::textInput('grand_total',$order->grand_total)?>&nbsp;<?=$order->currency?></td>
		<td style="border:1px solid #ddd"><?= @$selfJumiaOrderStatus[$order->order_status];//Html::dropDownList('order_status',$order->order_status,$selfJumiaOrderStatus)?></td>
		<td style="border:1px solid #ddd"><?=Html::dropDownList('default_shipping_method_code',$order->default_shipping_method_code, $carriers , ['prompt'=>'匹配物流'])?></td>
		<td style="border:1px solid #ddd"><?=Html::textInput('shipping_cost',$order->shipping_cost)?>&nbsp;<?=$order->currency?></td>
		<td style="border:1px solid #ddd"><?=Html::dropDownList('default_warehouse_id',$order->default_warehouse_id,$warehouses,['onchange'=>'load_shipping_method_for_control(this,"select[name=default_shipping_method_code]")'])?></td>
	</tr>
	</table>
	<br>
	</div>
</div>
<div>
	<!-- 收货地址信息 -->
	<h5>收货地址信息</h5>
	<div style="background-color: #f4f9fc">
	<table class="table table-bordered" style="font-size:12px;border:0px;">
	<tr>
		<td>买家姓名</td>
		<td>国家</td>
		<td>州</td>
		<td>城市</td>
		<td>地址1</td>
	</tr>
	<tr>
		<td><?=Html::textInput('consignee',$order->consignee,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_country',$order->consignee_country,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_province',$order->consignee_province,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_city',$order->consignee_city,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_address_line1',$order->consignee_address_line1,['style'=>'width:200px'])?></td>
	</tr>
	<tr>
		<td>地址2</td>
		<td>地址3</td>
		<td>邮编</td>
		<td>电话1</td>
		<td>电话2</td>
	</tr>
	<tr>
		<td><?=Html::textInput('consignee_address_line2',$order->consignee_address_line2,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_address_line3',$order->consignee_address_line3,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_postal_code',$order->consignee_postal_code,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_phone',$order->consignee_phone,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_mobile',$order->consignee_mobile,['style'=>'width:200px'])?></td>
	</tr>
	</table>
	</div>
</div>
<div>
	<!-- 商品信息 -->
	<h5>商品信息</h5>
	<div  style="background-color: #f4f9fc">
	<table class="table table-bordered" id="TableTransactionModify">
	<tr>
		<td>标题</td>
		<td>SKU</td>
		<td>数量</td>
		<td>价格</td>
		<td><?=Html::button('添加商品',['onclick'=>"TableTransactionModifyAdd()"]);?>
		<?php if (false&&count($order->items)>1):?>
			<?=Html::button('拆分订单',['onclick'=>"window.location.href='".Url::to(['/order/order/splitorder','orderid'=>$order->order_id])."'"]);?>
		<?php endif;?></td>
	</tr>
	<?php if (count($order->items)):foreach($order->items as $item):?>
	<tr>
		<td style="border:1px solid #ddd"><?=Html::textInput('item[product_name][]',$item->product_name)?></td>
		<td style="border:1px solid #ddd"><?=Html::textInput('item[sku][]',$item->sku)?></td>
		<td style="border:1px solid #ddd"><?=Html::textInput('item[quantity][]',$item->quantity)?></td>
		<td style="border:1px solid #ddd"><?=Html::textInput('item[price][]',$item->price)?></td>
		<td style="border:1px solid #ddd"><?=Html::input('hidden','item[itemid][]',$item->order_item_id)?></td>
	</tr>
	<?php endforeach;endif;?>
	</table>
	</div>
</div>
<center style="margin:8px 0"><?=Html::submitButton('保存',['class'=>'btn btn-success']);?>&nbsp;&nbsp;&nbsp;
<?=Html::submitButton('取消',['class'=>'btn btn-default','onclick'=>"window.close();"]);?></center>
</form>
<table>
<tr id="new" class="new hidden">
		<td style="border:1px solid #ddd"><?=Html::textInput('item[product_name][]')?></td>
		<td style="border:1px solid #ddd"><?=Html::textInput('item[sku][]')?></td>
		<td style="border:1px solid #ddd"><?=Html::textInput('item[quantity][]')?></td>
		<td style="border:1px solid #ddd"><?=Html::textInput('item[price][]')?></td>
		<td style="border:1px solid #ddd">
			<?=Html::input('hidden','item[itemid][]')?>
			<?=Html::button('删除',['onclick'=>"removeTransaction(this)"])?>
		</td>
	</tr>
</table>
