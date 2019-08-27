<?php	
use yii\helpers\Html;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderProduct;
use eagle\modules\order\models\OdOrderItem;

?>

<table class="table table-bordered tableset" >
<tr>
<td style="border:solid 1px black;text-align: center">图片</td>
<td style="border:solid 1px black;text-align: center">SKU</td>
<td style="border:solid 1px black;text-align: center">商品名</td>
<td style="border:solid 1px black;text-align: center">操作</td>
</tr>
		<?php if(!empty($rows)):?>
		<?php foreach ($rows as $row): ?>
		<?php //$oditem=OdOrderItem::find()->where(['order_id'=>$order->order_id])->one();?>
			<tr>
				<td style="border:solid 1px black;text-align: center;vertical-align:middle;" class="primary"><img src="<?=$row['photo_primary'] ?> >" style="width:50px;height:50px;"/></td>
				<td style="border:solid 1px black;text-align: center;vertical-align:middle;" class="sku"><?= $row['sku']?></td>
				<td style="border:solid 1px black;text-align: center;vertical-align:middle;" class="name"><?= $row['name'] ?></td>
				<td style="border:solid 1px black;text-align: center;width:10%;vertical-align:middle;" class="bind"><a href="javascript:return false;" style="text-decoration:none;" onclick="sku.bind(this)">绑定别名</a></td>
			</tr>
		<?php endforeach; endif;?>
	</table>

