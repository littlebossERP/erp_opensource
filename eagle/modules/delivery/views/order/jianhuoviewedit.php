<?php
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/delivery/order/list.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
?>
<br>
<div>
	<div>
		拣货单号:<?=$delivery->deliveryid?>  &nbsp;&nbsp;&nbsp;&nbsp;打印员:<?=$delivery->creater?>
		<hr>
	</div>
	<table class="table table-condensed table-bordered" style="font-size:12px;">
		<tr>
			<th>平台订单号</th>
			<th>图片</th>
			<th>sku</th>
			<th>数量</th>
			<th>商品名称</th>
			<th>属性</th>
			<th>货架号</th>
			<th>库存</th>
			<?php if (!$view):?>
			<th>操作</th>
			<?php endif;?>
		</tr>
		<?php if (count($orders)):foreach ($orders as $order):?>
			<?php 
			if (count($order->items)):$i=0;foreach ($order->items as $item):$i++;
			$skus=ProductApiHelper::getSkuInfo($item->sku, $item->quantity);
			if (count($skus)){
				$photo_primary = $skus[0]['photo_primary'];
			}else {
				$photo_primary = $item->photo_primary;
			}
			?>
		<tr>
			<?php if ($i==1):?>
			<td rowspan="<?=count($order->items)?>"><b><?=$order->order_id?></b></td>
			<?php endif;?>
			<td><img src="<?=$photo_primary?>" width="60px" height="60px"></td>
			<td><?=$item->sku?><br>
			<?php 
				if (count($skus)>=2):$j=0;
			?>
			[
			<?php foreach ($skus as $s):$j++;?>
			<?=$s['sku']?>*<?=$s['qty']?><?php if ($j<count($skus)):?><br><?php endif;?>
			<?php endforeach;?>
			]
			<?php endif;?>
			</td>
			<td><?=$item->quantity?></td>
			<td><?=$item->product_name?></td>
			<?php if (strlen($item->sku)):
				$stock=InventoryApiHelper::getPickingInfo([$item->sku],$order->default_warehouse_id);
				if (count($stock)):
			?>
			<td>
			<?php if (!empty($item->product_attributes)){
							$tmpProdAttr = json_decode($item->product_attributes,true);
							if (! is_array($tmpProdAttr)) {
								$tmp = explode('+',$item->product_attributes);
								$tmpProdAttr = [];
								foreach($tmp as $_tmp){
									$_tmpRT = explode(':', $_tmp);
									$tmpProdAttr[$_tmpRT[0]] = $_tmpRT[1];
								}
							}
							foreach($tmpProdAttr as $_tmpAttrKey =>$_tmpAttrVal){
								echo $_tmpAttrKey." : <b>".$_tmpAttrVal."</b><br>";
							}
						}?>
			</td>
			<td><?=$stock['0']['location_grid']?></td>
			<td><?=$stock['0']['qty_in_stock']?></td>
			<?php else:?>
			<td></td>
			<td></td>
			<td></td>
			<?php endif;?>
			<?php else:?>
			<td></td>
			<td></td>
			<td></td>
			<?php endif;?>
			<?php if (!$view):?>
			<?php if ($i==1):?>
			<td rowspan="<?=count($order->items)?>"><a href="#" onclick="javascript:cancelpicking('<?=$order->order_id?>')">删除订单</a></td>
			<?php endif;?>
			<?php endif;?>
		</tr>
			<?php endforeach;endif;?>
		<?php endforeach;endif;?>
	</table>
</div>
<?php if ($view==1):?>
<center><input type="button" value="打印" onclick="javascrīpt:window.print();"></center>
<?php endif;?>