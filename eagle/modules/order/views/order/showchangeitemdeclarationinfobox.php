<?php 
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
$puid = \Yii::$app->user->id;
?>
<form name="frm_declaration" method="POST">
<table class="table">
	<tr>
		<th>图片</th>
		<th>小老板订单号</th>
		<th>标题</th>
		<th>中文报关名</th>
		<th>英文报关名</th>
		<!--  <th>中文配货名称</th> -->
		<th>重量（g）</th>
		<th>价格（USD）</th>
		<th>海关编码</th>
		<th>影响范围</th>
		<th>操作</th>
	</tr>
	
<?php
foreach ( $items as $item){
if (!empty($item['sku']))
	$key = $item['sku'];
else
	$key = $item['product_name']; //sku 为空， 使用product name
?>
<tr>
	<td><img src="<?=  ((stripos($item['photo_primary'],'priceminister.com' )!==false ||stripos($item['photo_primary'],'cdscdn.com' )!==false ) ? eagle\modules\util\helpers\ImageCacherHelper::getImageCacheUrl( $item['photo_primary'], $puid, 1) :  $item['photo_primary']) ?>" style="max-width:50px;"></td>
	<td>
		<?=$productData[$item['order_item_id']]['order_id'] ?>
		<input type="hidden" name="order_itemid[]" value="<?=$productData[$item['order_item_id']]['order_item_id']?>">
		<?php  
			$text="{".(isset($productData[$item['order_item_id']]['nameCN'])?$productData[$item['order_item_id']]['nameCN']:'')."}{".(isset($productData[$item['order_item_id']]['nameEN'])?$productData[$item['order_item_id']]['nameEN']:'')."}{".(isset($productData[$item['order_item_id']]['weight'])?$productData[$item['order_item_id']]['weight']:'')."}{".(isset($productData[$item['order_item_id']]['price'])?$productData[$item['order_item_id']]['price']:'')."}{".(isset($productData[$item['order_item_id']]['code'])?$productData[$item['order_item_id']]['code']:'')."}";
		?><input type="hidden" name="json_itemid[]" value="<?php echo $text; ?>">
	</td>
	<td><?=$item['product_name'] ?>  <?php echo Html::hiddenInput('sku[]',((empty($item['sku']))?$item['product_name']:$item['sku']))?></td>
	<td><input type="text" name="nameCN[]" value="<?php echo ((empty($productData[$item['order_item_id']]['nameCN']))?'':$productData[$item['order_item_id']]['nameCN']) ?>"></td>
	<td><input type="text" name="nameEN[]" value="<?php echo ((empty($productData[$item['order_item_id']]['nameEN']))?'':$productData[$item['order_item_id']]['nameEN']) ?>"></td>
	<!--  <td><input type="text" name="ProdNameCN[]" value=""></td> -->
	<td><input type="text" name="weight[]" style="width: 60px;" value="<?php echo ((empty($productData[$item['order_item_id']]['weight']))?'':floor($productData[$item['order_item_id']]['weight']))?>"></td>
	<td><input type="text" name="price[]" style="width: 60px;" value="<?php echo ((empty($productData[$item['order_item_id']]['price']))?'':$productData[$item['order_item_id']]['price']) ?>"></td>
	<td><input type="text" name="code[]" value="<?php echo ((empty($productData[$item['order_item_id']]['code']))?'':$productData[$item['order_item_id']]['code']) ?>"></td>
	<td><?=Html::dropDownList('influencescope[]','',['1'=>'已付款与新订单','0'=>'当前订单'],['class'=>'do','style'=>'width:110px;margin:0px']);?></td>
	<td style="min-width: 60px;"><a name="ApplyToAll" qtipkey="apply_to_all">应用到所有</a></td>
</tr>
<?php 
}

foreach($OrderIdList as $orderid ){
	echo Html::hiddenInput('order_id[]',$orderid);
}
?>	
</table>
</form>
<div id="div_declaration_btn_bar"  class="text-center">
	<input type="button" id="btn_ok" class="iv-btn btn-primary" value="<?=TranslateHelper::t('确定')?>">
	<input type="button" id="btn_cancel" class="iv-btn btn-default" value="<?=TranslateHelper::t('取消')?>">
</div>