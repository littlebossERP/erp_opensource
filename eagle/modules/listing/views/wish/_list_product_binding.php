<?php
use eagle\modules\util\helpers\TranslateHelper;
?>

<div class="panel panel-default">
	<!-- Default panel contents -->
	<!-- <div class="panel-heading"><?= TranslateHelper::t('关联商品')?></div> -->

	<!-- Table -->
	<table class="table">
		<tr>
			<th><?= TranslateHelper::t('缩略图')?></th>
			<th><?= TranslateHelper::t('变参父产品ID')?></th>
			<th><?= TranslateHelper::t('刊登范本标题')?></th>
			<th><?= TranslateHelper::t('商品唯一码')?></th>
			<th><?= TranslateHelper::t('颜色/Size')?></th>
			<th><?= TranslateHelper::t('启用')?></th>
			<th><?= TranslateHelper::t('关联商品sku')?></th>
			<th><?= TranslateHelper::t('账号')?></th>
		</tr>
		<?php foreach($variants as $aVariant):?>
		<tr>
			<td><img alt="" src="<?= $fanbenInfo[$aVariant['fanben_id']]['main_image']?>"></td>
			<td><?= $aVariant['fanben_id']?></td>
			<td><?= $fanbenInfo[$aVariant['fanben_id']]['name']?></td>
			<td><?= $aVariant['sku']?></td>
			<td><?= $aVariant['color']."/".$aVariant['size']?></td>
			<td><input type="checkbox" <?= (strtoupper($aVariant['enable'])=='Y')?"checked":""?>/></td>
			<td><input type="text" class="form-control" name="relate_sku" value="<?= $aVariant['internal_sku'] ?>" data-id="<?= $aVariant['id']?>"  data-productid="<?= $aVariant['variance_product_id']?>"/></td>
			<td><?= $aVariant['fanben_id']?></td>
			
		</tr>
		
		<?php endforeach;?>
	</table>
</div>
