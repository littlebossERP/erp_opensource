<?php 
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\order\models\OdOrder;
?>
<style>
body{
	font-size: 13px;
    color: #333333;
}
.oneOrderDIV{
	float:left;
	width:470px;
/* 	min-height:283px; */
	border: 1px solid #ddd;
	margin: 0 20px 20px 0;
}
.top{
	width:470px;
	/* 	min-height:61px;*/
	border-bottom-color: rgb(221, 221, 221);
    border-bottom-style: solid;
    border-bottom-width: 1px;
}
.tright{
	text-align:right;
}
.tleft{
	text-align:left;
}
.goods{
	border-bottom-color: rgb(221, 221, 221);
    border-bottom-style: solid;
    border-bottom-width: 1px;
}
</style>
<div style="width:1000px;margin:auto;">
<?php foreach ($odDeliveryOrderDataArr as $orderid => $odDeliveryOrders){?>
<div class="oneOrderDIV">
		<div class="top">
			<div style="margin-top: 5px;margin-left:5px;text-align:center;float:left;">
			<img src="<?php echo Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$orderid,'font'=>0,'fontsize'=>0])?>"><br>
			<?= $orderid?>
			</div>
			<div style="margin-top: 5px;text-align:center;float:right;">
			<table>
			<tr>
			<th class="tright">拣货单：</th><td><?php echo $odDeliveryDataArr['deliveryid']?></td>
			</tr>
			<?php if ($is_show_platform_order_id !='N'){?>
			<tr>
			<th class="tright">平台订单号：</th>
			<td>
			<?php 
			$order = OdOrder::findOne(['order_id'=>$orderid]);
			if ($order['order_source'] == 'ebay'){
				echo $order['order_source_srn'];
			}else{
				echo $order['order_source_order_id'];
			}
			?>
			</td>
			</tr>
			<?php }?>
			<tr>
			<th class="tright">打印时间：</th><td><?php echo date("Y-m-d H:i:s",$odDeliveryDataArr['print_picking_time']);?></td>
			</tr>
			<tr>
			<th class="tright">打印人：</th><td><?php echo $odDeliveryDataArr['print_picking_operator'];?></td>
			</tr>
			</table>
			</div>
			<div style="clear: both;"></div>
		</div>
		<?php foreach ($odDeliveryOrders  as $odDeliveryOrder){?>
		<div class="goods">
			<table>
			<tr>
			<th class="tleft"><img src="<?php echo $odDeliveryOrder['image_adress'];?>" width='60px' height='60px'></th>
			<th class="tleft" width=210px><?= $odDeliveryOrder['good_name']?><br><?= $odDeliveryOrder['sku'].' x '.$odDeliveryOrder['count']?></th>
			<th class="tleft" width=200px><?= '属性:'.$odDeliveryOrder['good_property'];?><br><?= '货位:'.$odDeliveryOrder['location_grid'];?></th>
			</tr>
			</table>
		</div>
		<?php }?>
</div>
<?php }?>
</div>
   