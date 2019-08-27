<?php
use yii\helpers\Html;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderProduct;
use eagle\modules\order\models\OdOrderItem;

?>
<style>
	.top{margin:0 auto 20px;height:30px;width:100%;}
	.tableset{text-align:center;border:solid 1px black;}
	.tableset tr{border:solid 1px black;}
</style>
<form>
	<div class="top">
		SKU别名：<input type="text" />
		SKU：<input type="text"  />
		商品名：<input type="text" />&nbsp;&nbsp;
		<button onclick="javascript:return false;">筛选</button>
	</div>
	<table class="table table-bordered tableset">
		<tr>
			<td style="border:solid 1px black;">图片</td>
			<td style="border:solid 1px black;">SKU</td>
			<td style="border:solid 1px black;">商品名</td>
			<td style="border:solid 1px black;">操作</td>
		</tr>
		<?php foreach ($orders as $order): ?>
		<?php $oditem=OdOrderItem::find()->where(['order_id'=>$order->order_id])->one();?>
			<tr>
				<td style="border:solid 1px black;"><img src="<?=$oditem->photo_primary ?>>"/></td>
				<td style="border:solid 1px black;"><?= $oditem->sku?></td>
				<td style="border:solid 1px black;"><?= $oditem->product_name ?></td>
				<td style="border:solid 1px black;"><a href="javascript:return false;" style="text-decoration:none;">绑定别名</a></td>
			</tr>
		<?php endforeach; ?>
	</table>
</form>