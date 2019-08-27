<?php 
use yii\helpers\Html;
use yii\helpers\Url;
?>

<?php //print_r($lists)?>
<?php //print_r($orderLists)?>

<style>
body{
	font-size: 13px;
    color: #333333;
}
.oneOrderDIV{
	float:left;
	width:460px;
/* 	min-height:283px; */
	border: 1px solid #ddd;
	margin: 0 15px 15px 0;
}
.toDIV{
	width:450px;
/* 	min-height:77px; */
	border-bottom-color: rgb(221, 221, 221);
    border-bottom-style: solid;
    border-bottom-width: 1px;
	padding:6px;
}
.skuDIV{
	width:460px;
	min-height:70px;
	border-bottom-color: rgb(221, 221, 221);
    border-bottom-style: solid;
    border-bottom-width: 1px;
}
.skuDIV>img{
	float:left;
	width:70px;
	height:70px;
	border-right-color: rgb(221, 221, 221);
}
.showDIV{
	width:460px;
	padding:6px;
}
.itemMoreDIV{
	margin-left:66px;
}
.itemMoreDIV>div{
	min-height:28px;
	line-height:18px;
}
.itemMoreDIV>div+div{
	font-weight:bold;
}
.toDIV-l{
	float:left;
}
.toDIV-r{
	padding:0 5px;
	float:left;
}
</style>
<div style="width:1000px;margin:auto;">
<?php foreach ($orderLists as $orderList){?>
<div class="oneOrderDIV">
	<div class="toDIV">
	<div class="toDIV-l">
		<img src="<?php echo Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$orderList['order_id'],'font'=>0,'fontsize'=>0])?>">
	</div>
	<div class="toDIV-r">
	<b>小老板单号:</b><?= $orderList['order_id']?><br>
	<b>平台订单号:</b><?php echo $orderList['order_source']=='ebay'?$orderList['order_source_srn']:$orderList['order_source_order_id'];?>
	</div>
	<div class="toDIV-r" style="text-align: right;">
	<b><?php echo date('Y-m-d H:i:s',time())?></b>
	</div>
	<div style="clear: both;"></div>
	</div>
	<div class="toDIV">
	<b>To:</b><?= $orderList['consignee']?> <b>Phone:</b><?= empty($orderList['consignee_mobile'])?$orderList['consignee_phone']:$orderList['consignee_mobile']?><br>
	<b>Address:</b><?php echo $orderList['consignee_address_line1']." ".$orderList['consignee_address_line2']." ".$orderList['consignee_address_line3']." ".$orderList['consignee_county']." ".$orderList['consignee_district']." ".$orderList['consignee_city']." ".$orderList['consignee_province']." ".$orderList['consignee_country']?>
	</div>	
	<?php if(empty($lists[$orderList['order_id']])){
		echo '不存在的订单或没有任何数据！';
	}else{foreach ($lists[$orderList['order_id']] as $list){
		$name = isset($list['prod_name_ch'])?$list['prod_name_ch']:@$list['product_name'];

	?>
		<div class="skuDIV">
		<img src="<?php echo $list['photo_primary']?>">
			<div class="itemMoreDIV">
				<div><?= $name?></div>
				<div>
					<?= $list['sku'].' x '.$list['qty'].'&nbsp;属性:'.$list['product_attributes'].'&nbsp;货位:'.$list['location_grid'];?>
				</div>
			</div>
		</div>
	<?php }}?>
	<div class="showDIV">
		<div><?= '订单备注:'.$orderList['desc']?></div>
		<div>
		<div class="toDIV-l">
			<?php $barcode_data = empty($list['tracking_number'])?$orderList['customer_number']:$list['tracking_number'];?>
			<img src="<?php echo Url::to(['/carrier/carrieroperate/barcode','codetype'=>'code128','thickness'=>'30','text'=>$barcode_data,'font'=>0,'fontsize'=>0])?>">
		</div>
		<div class="toDIV-r">
			<?=isset($carriers[$orderList['default_carrier_code']])?$carriers[$orderList['default_carrier_code']]:$orderList['default_carrier_code']; ?>-
			<?=isset($services[$orderList['default_shipping_method_code']])?$services[$orderList['default_shipping_method_code']]:$orderList['default_shipping_method_code']; ?>
			<br><?= $barcode_data?>
		</div>
		</div>
	</div>
</div>
<?php }?>
</div>