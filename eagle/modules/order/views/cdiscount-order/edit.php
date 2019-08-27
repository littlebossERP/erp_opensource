<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\helpers\CdiscountOrderInterface;

$this->registerJsFile(\Yii::getAlias('@web')."/js/lib/ystep/ystep.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerCssFile(\Yii::getAlias('@web')."/js/lib/ystep/ystep.css", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderOrderEdit.js", ['depends' => ['yii\web\JqueryAsset']]);
?>
<form action="" method="post" class="panel-edit-order">
<div>
	<?=Html::input('hidden','orderid',$order->order_id)?>
	<?=Html::input('hidden','statushide',$order->order_status,['id'=>'statushide'])?>
	<br>
	<div>
		<font color="#8b8b8b" font-size="12px"><?= TranslateHelper::t('小老板流水号')?> : <b><?=$order->order_id?></b>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<?= TranslateHelper::t('CD订单号')?> : <b><?=$order->order_source_order_id?></b>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		下单日期:<b><?=$order->order_source_create_time>0?date('Y-m-d H:i:s',$order->order_source_create_time):''?></b>
		</font>
	</div>
	<!-- 基本信息 -->
	<h5>基本信息</h5>
	<div style="background-color: #f4f9fc">
	<div class="ystep1" style="text-align:center"></div>
	<br>
	<table class="table table-bordered" style="font-size:12px;border:0px;">
	<tr>
		<td>买家账号</td>
		<td>订单总金额</td>
		<td>订单状态</td>
		<td>匹配物流</td>
		<td>运费</td>
		<td>匹配仓库</td>
	</tr>
	<tr style="background-color: #fff">
		<td style="border:1px solid #ddd"><?=$order->source_buyer_user_id?></td>
		<td style="border:1px solid #ddd"><?=Html::textInput('grand_total',$order->grand_total)?>&nbsp;<?=$order->currency?></td>
		<?php 
			$movearr = OdOrder::$status;
			unset($movearr[100]);
// 			unset($movearr[300]); 
		?>
		<td style="border:1px solid #ddd">
			<?php $status=odorder::getOrderStatus(); 
				if(isset($status[(string)$order->order_status]))
					echo $status[(string)$order->order_status];
				else 
					echo '--';
			?>
		</td>
		<td style="border:1px solid #ddd"><?=Html::dropDownList('default_shipping_method_code',$order->default_shipping_method_code,CarrierApiHelper::getShippingServices2_1())?></td>
		<td style="border:1px solid #ddd"><?=Html::textInput('shipping_cost',$order->shipping_cost)?>&nbsp;<?=$order->currency?></td>
		<td style="border:1px solid #ddd"><?php $warehouses = InventoryApiHelper::getWarehouseIdNameMap();?><?=Html::dropDownList('default_warehouse_id',$order->default_warehouse_id,$warehouses)?></td>
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
		<td>Email</td>
		<td>国家</td>
		<td>邮编</td>
		<td>电话</td>
		<td>手机</td>
	</tr>
	<tr>
		<td><?=Html::textInput('consignee',$order->consignee,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_email',$order->consignee_email,['style'=>'width:200px'])?></td>
		<td>
			<?=Html::dropDownList('consignee_country_code',$order->consignee_country_code,$countrys,['prompt'=>'国家','style'=>'width:100px'])?>
			<?=Html::textInput('consignee_country',$order->consignee_country)?>
		</td>
		<td><?=Html::textInput('consignee_postal_code',$order->consignee_postal_code,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_phone',$order->consignee_phone,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_mobile',$order->consignee_mobile,['style'=>'width:200px'])?></td>
	</tr>
	<tr>
		<!-- <td>州/省</td> -->
		<td>城市</td>
		<td>郡/县</td>
		<td>地区</td>
		<td>公司名</td>
		<td>地址1</td>
		<td>地址2</td>
	</tr>
	<tr>
		<!-- <td><?=Html::textInput('consignee_province',$order->consignee_province,['style'=>'width:200px'])?></td> -->
		<td><?=Html::textInput('consignee_city',$order->consignee_city,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_county',$order->consignee_county,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_district',$order->consignee_district,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_company',$order->consignee_company,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_address_line1',$order->consignee_address_line1,['style'=>'width:200px'])?></td>
		<td><?=Html::textInput('consignee_address_line2',$order->consignee_address_line2,['style'=>'width:200px'])?></td>
		
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
		<?php if (count($order->items)>1):?>
			<?=Html::button('拆分订单',['onclick'=>"window.location.href='".Url::to(['/order/order/splitorder','orderid'=>$order->order_id])."'"]);?>
		<?php endif;?></td>
	</tr>
	<?php if (count($order->items)):foreach($order->items as $item):?>
	<?php if(in_array($item->sku, CdiscountOrderInterface::getNonDeliverySku())) continue;?>
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
<center><?=Html::submitButton('保存',['class'=>'btn btn-success']);?>&nbsp;&nbsp;&nbsp;
<?=Html::submitButton('取消',['class'=>'btn btn-default','onclick'=>"window.close();"]);?></center>
</form>
<table>
	<tr id="new" style="display:none">
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
<?php 
$this->registerJs('$(function(){
	$(".panel-edit-order select[name=\'consignee_country_code\']").change(function(){
		var code = $(".panel-edit-order select[name=\'consignee_country_code\']").val();
		$(".panel-edit-order input[name=\'consignee_country\']").val(code);
	});
});' , \yii\web\View::POS_READY);
?>
<script>
function TableTransactionModifyAdd(){
	$('#TableTransactionModify').append($('#new').clone().css('display','table-row'));
}
function  removeTransaction(obj){
	$(obj).parent().parent().remove();
}
</script>