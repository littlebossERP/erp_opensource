<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\order\helpers\OrderHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/lib/ystep/ystep.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerCssFile(\Yii::getAlias('@web')."/js/lib/ystep/ystep.css", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/orderCommon.js?v=".OrderHelper::$OrderCommonJSVersion, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/order/aliexpressOrder/edit.js", ['depends' => ['yii\web\JqueryAsset']]);

$this->registerCssFile($baseUrl."css/order/aliexpressOrder.css", ['depends'=>["eagle\assets\AppAsset"]]);

$this->registerJs("initSelectConsigneeCountryCode();" , \yii\web\View::POS_READY);
$this->registerJs("initTableInputValidation();" , \yii\web\View::POS_READY);

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

<form id="frm_order_edit" action="" method="post">
	<?=Html::input('hidden','orderid',$order->order_id)?>
	<h1>编辑订单</h1>
	
	<div class="panel-edit-order">
		<div class="panel-edit-order-head">
		<?=Html::label('流水号:',null,['class'=>'edit-order-label'])?><span><?=$order->order_id?></span>
		<?=Html::label('平台订单号：',null,['class'=>'edit-order-label'])?><span><?=$order->order_source_order_id?></span>
		</div>
	<div class="panel-edit-order-body">
		<div class="order-edit-row">
			<?=html::label('基本信息：',null,['class'=>'edit-order-title-label'])?>
			
			<table  class="table table-bordered" style="font-size:12px;border:0px;">
				<tr>
					<td><?=Html::label('卖家账号:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=$order->selleruserid?></b></td>
					<td><?=Html::label('买家账号:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=$order->source_buyer_user_id?></b></td>
					<td><?=Html::label('匹配仓库:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><?php $warehouses = InventoryApiHelper::getWarehouseIdNameMap();?><?=Html::dropDownList('default_warehouse_id',$order->default_warehouse_id,$warehouses,$select_attr)?></td>
					<td><?=Html::label('运输服务:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><?=Html::dropDownList('default_shipping_method_code',$order->default_shipping_method_code,CarrierApiHelper::getShippingServices2_1(),$select_attr+=['prompt'=>'请选择物流服务'])?></td>
				</tr>
			</table>
		</div>
		<div class="order-edit-row">
			<?=html::label('状态信息：',null,['class'=>'edit-order-title-label'])?>
			<table  class="table table-bordered" style="font-size:12px;border:0px;">
				<tr>
					<td><?=Html::label('订单状态:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?= OdOrder::$status[$order->order_status] ?></b></td>
					<td><?=Html::label('AliExpress订单状态:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=OdOrder::$aliexpressStatus[$order->order_source_status]?></b></td>
					<td><?=Html::label('好评:',null,['class'=>'edit-order-label'])?></td><td class="text-left"></td>
					<td><?=Html::label('通知平台发货:',null,['class'=>'edit-order-label'])?></td><td class="text-left"></td>
				</tr>
			</table>
		</div>
		
		<div class="order-edit-row">	
			<?=html::label('时间记录：',null,['class'=>'edit-order-title-label'])?>
			<table  class="table table-bordered" style="font-size:12px;border:0px;">
				<tr>
					<td><?=Html::label('下单日期:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->order_source_create_time)?></b></td>
					<td><?=Html::label('付款日期:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->paid_time);?></b></td>
					<td><?=Html::label('通知平台发货日期:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->order_source_create_time)?></b></td>
					<td><?=Html::label('打单日期:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->paid_time);?></b></td>
					<td><?=Html::label('下单日期:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->order_source_create_time)?></b></td>
					<td><?=Html::label('出库日期:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=date('Y-m-d H:i:s',$order->paid_time);?></b></td>
				</tr>
			</table>
			
		</div>
		<div class="order-edit-row">
			<?=html::label('资金信息：',null,['class'=>'edit-order-title-label'])?>
			<table  class="table table-bordered" style="font-size:12px;border:0px;">
				<tr>
					<td><?=Html::label('商品总金额:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=$order->subtotal?> <?=$order->currency?> </b></td>
					<td><?=Html::label('买家支付运费:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?= $order->shipping_cost?> <?=$order->currency?> </b></td>
					<td><?=Html::label('订单总金额:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><?= $order->grand_total?> <?=$order->currency?> </td>
					<td><?=Html::label('交易费:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><?=$order->commission_total?> <?=$order->currency?> </td>
				</tr>
				<tr>
					<td><?=Html::label('预估运费:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;">￥<?=$order->antcipated_shipping_cost?></b></td>
					<td><?=Html::label('实际运费:',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;">￥<?=Html::textInput("actual_shipping_cost", $order->actual_shipping_cost);?></b></td>
				</tr>
			</table>
			
		</div>
		<div class="order-edit-row">
			<div class="row">
				<div class="col-md-6"><?=html::label('原始收件人信息：',null,['class'=>'edit-order-title-label'])?></div>
				<div class="col-md-5"><?=html::label('收件人信息：',null,['class'=>'edit-order-title-label'])?></div>
				<div class="col-md-1"><?=Html::button('保存',['class'=>"iv-btn btn-sm pull-right",'id'=>'save_consignee','onclick'=>"javascript:saveConsigneeInfo('".$order->order_id."');"])?></div>
								
			</div>
			<div class="row">
				<div id="origin_consignee_info" class="col-md-6">
				<?php 
					if (!empty($order->origin_shipment_detail)){
						$consigneeInfo = json_decode($order->origin_shipment_detail,true);
					}else{
						$consigneeInfo = [];
					}
					
				?>
					
					<div class="row">
						<div class="col-md-1"><?=Html::label('收件人：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-3"><?= html::label(empty($consigneeInfo)?$order->consignee:$consigneeInfo['consignee'])?></div>
						<div class="col-md-1"><?=Html::label('公司：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-3"><?= html::label(empty($consigneeInfo)?$order->consignee_company:$consigneeInfo['consignee_company'])?></div>
						<div class="col-md-1"><?=Html::label('Email：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-3"><?= html::label(empty($consigneeInfo)?$order->consignee_email:$consigneeInfo['consignee_email'])?></div>
					</div>
					
					<div class="row">
						<div class="col-md-1"><?=Html::label('Mobile：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-3"><?= html::label(empty($consigneeInfo)?$order->consignee_mobile:$consigneeInfo['consignee_mobile'])?></div>
						<div class="col-md-1"><?=Html::label('Phone：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-3"><?= html::label(empty($consigneeInfo)?$order->consignee_phone:$consigneeInfo['consignee_phone'])?></div>
						<div class="col-md-1"><?=Html::label('Fax：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-3"><?= html::label(empty($consigneeInfo)?$order->consignee_fax:@$consigneeInfo['consignee_fax'])?></div>
					</div>
					
					<div class="row">
						<div class="col-md-1"><?=Html::label('国家：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-2"><?= html::label(empty($consigneeInfo)?$order->consignee_country:$consigneeInfo['consignee_country'])?></div>
						<div class="col-md-1"><?=Html::label('州/省：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-2"><?= html::label(empty($consigneeInfo)?$order->consignee_province:$consigneeInfo['consignee_province'])?></div>
						<div class="col-md-1"><?=Html::label('市：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-2"><?= html::label(empty($consigneeInfo)?$order->consignee_city:$consigneeInfo['consignee_city'])?></div>
						<div class="col-md-1"><?=Html::label('邮编：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-2"><?= html::label(empty($consigneeInfo)?$order->consignee_postal_code:$consigneeInfo['consignee_postal_code'])?></div>
					</div>
					
					<div class="row">
						<div class="col-md-1"><?=Html::label('街道1：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-11"><?= html::label(empty($consigneeInfo)?$order->consignee_address_line1 :$consigneeInfo['consignee_address_line1'])?></div>
					</div>
					
					<div class="row">
						<div class="col-md-1"><?=Html::label('街道2：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-11"><?= html::label(empty($consigneeInfo)?$order->consignee_address_line2 :$consigneeInfo['consignee_address_line2'])?></div>
					</div>
					
					<div class="row">
						<div class="col-md-1"><?=Html::label('街道3：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-11"><?= html::label(empty($consigneeInfo)?$order->consignee_address_line3 :$consigneeInfo['consignee_address_line3'])?></div>
					</div>
				
				</div>
				<div id="div_consignee_info"  class="col-md-6">
					<div class="row">
						<div class="col-md-1"><?=Html::label('收件人：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-3"><?=Html::textInput('consignee',$order->consignee)?></div>
						
						<div class="col-md-1"><?=Html::label('公司：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-3"><?=Html::textInput('consignee_company',$order->consignee_company)?></div>
						<div class="col-md-1"><?=Html::label('Email：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-3"><?=Html::textInput('consignee_email',$order->consignee_email)?></div>
					</div>
					
					<div class="row">
						<div class="col-md-1"><?=Html::label('Mobile：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-3"><?=Html::textInput('consignee_mobile',$order->consignee_mobile)?></div>
						<div class="col-md-1"><?=Html::label('Phone：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-3"><?=Html::textInput('consignee_phone',$order->consignee_phone)?></div>
						<div class="col-md-1"><?=Html::label('Fax：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-3"><?=Html::textInput('consignee_fax',$order->consignee_fax)?></div>
					</div>
					
					<div class="row">
						<?php 
						$countryList = StandardConst::$COUNTRIES_CODE_NAME_EN;
						unset($countryList['--']);
						?>
						<div class="col-md-1"><?=Html::label('国家：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-2"><?= html::dropDownList('consignee_country_code',$order->consignee_country_code ,$countryList )?>
							<?= html::hiddenInput('consignee_country' ,$order->consignee_country )?>
						</div>
						<div class="col-md-1"><?=Html::label('州/省：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-2"><?=Html::textInput('consignee_province',$order->consignee_province)?></div>
						<div class="col-md-1"><?=Html::label('市：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-2"><?=Html::textInput('consignee_city',$order->consignee_city)?></div>
						<div class="col-md-1"><?=Html::label('邮编：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-2"><?=Html::textInput('consignee_postal_code',$order->consignee_postal_code)?></div>
					</div>
					
					<div class="row">
						<div class="col-md-1"><?=Html::label('街道1：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-11"><?=Html::textInput('consignee_address_line1',$order->consignee_address_line1 )?></div>
					</div>
					<div class="row">
						<div class="col-md-1"><?=Html::label('街道2：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-11"><?=Html::textInput('consignee_address_line2',$order->consignee_address_line2 )?></div>
					</div>
					<div class="row">
						<div class="col-md-1"><?=Html::label('街道3：',null,['class'=>'edit-order-label'])?></div>
						<div class="col-md-11"><?=Html::textInput('consignee_address_line3',$order->consignee_address_line3 )?></div>
					</div>
				
				</div>
			
			</div>
			
		</div>
	
		<div class="order-edit-row">
			<?=html::label('物流信息：',null,['class'=>'edit-order-title-label'])?>
			<table  class="table table-bordered" style="font-size:12px;border:0px;">
			<?php 
			$MarkStatus = ['0'=>'等待标记' , '1'=>'标记成功' , '2'=>'标记失败'];
			foreach($ordershipped as $row):
				if ($row['addtype'] == '手动标记发货' ):?>
					<tr>
						<td><?=Html::label('国际物流方式：',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=$row['shipping_method_name']?></b></td>
						<td><?=Html::label('跟踪号：',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=$row['tracking_number']?></b></td>
						<td><?=Html::label('标记状态：',null,['class'=>'edit-order-label'])?></td><td class="text-left"><?= @$MarkStatus[$row['status']] .(($row['status']==1)?"(小老板标记)":"" ) ?></td>
						<td><?=Html::label('标记时间：',null,['class'=>'edit-order-label'])?></td><td class="text-left"><?= date('Y-m-d H:i:s',$row['created'])?> </td>
						<td><?=Html::label('标记类型：',null,['class'=>'edit-order-label'])?></td><td class="text-left"><?= $row['signtype']?></td>
						<td><?=Html::label('备注：',null,['class'=>'edit-order-label'])?></td><td class="text-left"><?= $row['description']?></td>
						
					</tr>
				<?php else:?>
					<tr>
						<td><?=Html::label('国际物流方式：',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=$row['shipping_method_name']?></b></td>
						<td><?=Html::label('跟踪号：',null,['class'=>'edit-order-label'])?></td><td class="text-left"><b style="color:#637c99;"><?=$row['tracking_number']?></b></td>
						<td><?=Html::label('发货时间：',null,['class'=>'edit-order-label'])?></td><td class="text-left"></td>
						<td><?=Html::label('妥投时间：',null,['class'=>'edit-order-label'])?></td><td class="text-left"></td>
					</tr>
				<?php endif;?>
				
			<?php endforeach;?>
				
			</table>
		</div>
		<div class="order-edit-row">
			<?=html::label('商品信息：',null,['class'=>'edit-order-title-label'])?>
			<table class="table table-bordered" id="TableTransactionModify" style="font-size:12px;border:0px;">
			<tr>
				<td>标题</td>
				<td>SKU</td>
				<td>数量</td>
				<td>
				<?php if ($order->order_status == OdOrder::STATUS_PAY):
					echo Html::button('添加商品',['onclick'=>"TableTransactionModifyAdd()"]);
					if (count($order->items)>1):
					endif;
				endif;?></td>
			</tr>
			<?php 
			
			
			if (count($order->items)):foreach($order->items as $item):?>
			<tr>
				<td><?=Html::textInput('item[product_name][]',$item->product_name, $input_attr) ?></td>
				<td><?=Html::textInput('item[sku][]',$item->sku, $input_attr)?></td>
				<td><?=Html::textInput('item[quantity][]',$item->quantity, $input_attr)?></td>
				<td><?=Html::input('hidden','item[itemid][]',$item->order_item_id, $input_attr)?></td>
			</tr>
			<?php endforeach;endif;?>
			</table>
		</div>
	</div>
	</div>
	

<center>
<?=Html::Button('保存',['class'=>'btn btn-success','onclick'=>"saveOrderInfo();"]);?>&nbsp;&nbsp;&nbsp;
<?=Html::Button('取消',['class'=>'btn btn-default','onclick'=>"window.close();"]);?>
</center>
</form>

<table>
<tr id="new" style="display:none">

		<td><?=Html::textInput('item[product_name][]')?></td>
		<td><?=Html::textInput('item[sku][]')?></td>
		<td><?=Html::textInput('item[quantity][]')?></td>
		<td>
			<?=Html::input('hidden','item[itemid][]')?>
			<?=Html::button('删除',['onclick'=>"removeTransaction(this)"])?>
		</td>
	</tr>
	</table>
	<?php 
$this->registerJs('$(function(){
	$(".panel-edit-order select[name=\'consignee_country_code\']").change(function(){
		var code = $(".panel-edit-order select[name=\'consignee_country_code\']").val();
		$(".panel-edit-order select[name=\'consignee_country\']").val(code);
	});
});' , \yii\web\View::POS_READY);
?>