<?php 
use \Yii;
use eagle\modules\catalog\helpers\ProductApiHelper;
use common\helpers\Helper_Array;
use yii\helpers\Url;
$i = 0;
foreach ($orders as $order){
	$i++;
	$products = array();
?>	
<?php foreach ($order->items as $one){
		if (ProductApiHelper::hasProduct($one->sku)){
			$productInfo = ProductApiHelper::getProductInfo($one->sku);
			if(empty($productInfo)){
				@$products[$one->sku] = array('sku'=>$one->sku,'name'=>$one->product_name);
				@$products[$one->sku]['quantity']+=$one->ordered_quantity;
			}else{
				if (isset($productInfo['children'])){
					foreach ($productInfo['children'] as $v){
						@$products[$v['sku']] = array('sku'=>$v['sku'],'name'=>$v['name']);
						//需要乘以绑定的子商品数量
						@$products[$v['sku']]['quantity']+=$one->ordered_quantity;

					}
				}else{
					@$products[$productInfo['sku']] = array('sku'=>$productInfo['sku'],'name'=>$productInfo['name']);
					@$products[$productInfo['sku']]['quantity']+=$one->ordered_quantity;
						
				}
			}
		}else{
			@$products[$one->sku] = array('sku'=>$one->sku,'name'=>$one->product_name);
			@$products[$one->sku]['quantity']+=$one->ordered_quantity;
		}
	}?>
	<div style='border:1px black solid;width:45%;height:280px;float:left;margin:1%;padding:5px;'>
	<div style='width:40%;float:left;'>
	<strong><?php echo $order->order_id?></strong><br>
	<strong style="font-size:8pt;"><?php echo @$carriers[$order->default_carrier_code];?></strong><br>
	<strong style="font-size:8pt;"><?php echo @$shipping_services[$order->default_shipping_method_code];?></strong><br>
	</div>
	<div style='width:60%;float:left;font-size:8pt;'>
	TO<br>
	收件人 :<?php echo $order->consignee?><br>
	地址:<?php echo $order->consignee_address_line1." ".$order->consignee_address_line2." ".$order->consignee_address_line3?><br>
	  <?php echo $order->consignee_county." ".$order->consignee_district." ".$order->consignee_city." ".$order->consignee_province." ".$order->consignee_country."(".$order->consignee_country_code.")"?><br>
	邮编:<?php echo $order->consignee_postal_code?><br>
	电话:<?php echo $order->consignee_phone?>
	</div>
	<hr>
	<?php foreach ($products as $product){?>
	<strong><?php echo $product['sku']?></strong> * <strong><?php echo $product['quantity']?></strong> <font style="font-size:8pt;">[<?php echo $product['name']?>]</font><br>
	<?php }?>
	</div>
<?php if ($i%6==0){?>
<div style="page-break-after: right;"></div>
<?php }?>
<?php }?>
