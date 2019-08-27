<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile($baseUrl."css/tracking/tracking.css", ['depends'=>["eagle\assets\AppAsset"]]);

$this->title='Jumia订单标记发货';
$this->params['breadcrumbs'][] = $this->title;
?>
<style>
.table td,.table th {
	text-align: center;
}

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
</style>

<br>
<form action="<?=Url::to(['/order/jumia-order/signshippedsubmit'])?>" method="post">
<table class="table table-striped">
<thead>
<tr>
<th width="5%">平台订单号</th>
<th width="5%">jumia订单号</th>
<th width="10%">标记物流服务</th>
<th width="10%">物流号</th>
</tr>
</thead>
<?php if (count($orders)):foreach ($orders as $order):
$odship = OdOrderShipped::find()->where(['order_id'=>$order->order_id])->orderBy('id DESC')->one();
if ($odship==null){
$odship = new OdOrderShipped();
}
?>
<tbody>
	<tr>
	<td><font class="text-warning" style="font-weight: bold;"><?=$order->order_id?></font><?=Html::input('hidden','order_id[]',$order->order_id)?></td>
	<td><b><?=$order->order_source_order_id?><?=Html::input('hidden','order_source_order_id[]',$order->order_source_order_id)?></b></td>
	<td>
		<?php if (strlen($odship->shipping_method_code)>0){
			$method = $odship->shipping_method_code;
		}else{
			if (strlen($order->default_shipping_method_code)>0){
				$method = CarrierApiHelper::getServiceCode($order->default_shipping_method_code,$order->order_source);
			}else{
				$method = CarrierApiHelper::getDefaultServiceCode($order->order_source);
			}
		}?>
		<?=Html::dropDownList("shipmethod[$order->order_id]",$method,$jumiaShippingMethod,['prompt'=>'物流服务','style'=>'width:150px;','id'=>'carrier_code','class'=>'eagle-form-control'])?>
	</td>
	<td><?=Html::input('',"tracknum[$order->order_id]",@$odship->tracking_number,['size'=>15,'class'=>'eagle-form-control','placeholder'=>"物流跟踪号"])?></td>
	</tr>
</tbody>
<?php endforeach;endif;?>
</table>
<div style="text-align:center;">
<?=Html::submitButton('标记发货',['class'=>'btn btn-success'])?>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?=Html::button('取消',['class'=>'btn','onclick'=>'window.close();'])?>
</div><br><br>
</form>

