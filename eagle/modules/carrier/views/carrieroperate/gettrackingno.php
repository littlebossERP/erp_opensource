<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\modules\carrier\models\SysShippingService;
use yii\helpers\Url;
$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/carrier/submitOrder.js", [ 
		'depends' => [ 
				'yii\web\JqueryAsset' 
		] 
] );
?>
<script>
	var submitOrderUrl = '<?=Url::to("get-data") ?>';
	var operate_ids = '<?= $ids ?>';
</script>
<?php echo  CarrierHelper::jindutiao(CarrierHelper::returnAction([0,1,2,3,6]),$nums,CarrierHelper::returnAction($step),$ids);?>
<!-- table -->

<link href="/css/carrier/carrier.css" rel="stylesheet" type="text/css" />
<div style="" class="container-fluid" id="getOrderNo_main">
<input type="button" value="获取物流号" id="gettrackingno" class="btn btn-success" style=" margin-bottom: 10px">
	<div class="getOrderNo_title">
		<ul>
			<li>平台订单号</li>
			<li>客户参考号</li>
			<li>物流商</li>
			<li>运输方式</li>
			<li>处理结果</li>
		</ul>
	</div>
<?php
if($orderObjs):
foreach($orderObjs as $orderObj):
?>
<div class="result" result="">
<form>
<input type="hidden" name="id" value="<?= $orderObj->order_id;?>">
<div class="getOrderNo_order">
	<ul>
		<li><?=$orderObj->order_id ?></li>
		<li><?=$orderObj->customer_number ?></li>
		<li>
		<?php 
			$shippingService = SysShippingService::find()->where(['id'=>$orderObj->default_shipping_method_code])->one();
			if ($shippingService !==null){
				echo $shippingService->carrier_name;
			}else{
				echo '未匹配到物流商';
			}
		?></li>
		<li><?=isset($services[$orderObj->default_shipping_method_code])?$services[$orderObj->default_shipping_method_code]:'未匹配到运输服务'?></li>
		<li class="message2"><?=$orderObj->carrier_error?'上次操作错误信息：<br/>'.$orderObj->carrier_error:''; ?></li> 
	</ul>
</div>
</form>
</div>
<div style="clear:both"></div>
<?php endforeach;else:echo '该状态下没有订单';endif;?>

</div>