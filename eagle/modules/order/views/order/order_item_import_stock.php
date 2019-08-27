<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>
<div>

</div>

<?php foreach($ItemGroup as $warehouse =>$itemList):?>
<div><?= $warehouseids[$warehouse];?></div>	

<table class="table" data-warehouse-id="<?=$warehouse?>">
	<thead>
		<tr>
			<th><?=TranslateHelper::t('图片')?></th>
			<th><?=TranslateHelper::t('SKU')?></th>
			<th><?=TranslateHelper::t('入库数量')?></th>
			<th><?=TranslateHelper::t('货位')?></th>
		</tr>
		
	</thead>
	<tbody>
	<?php foreach ($itemList as $sku=>$qty):?>
		<tr>
			<td><?= Html::img(@$ItemInfoGroup[$sku]['photo_primary'],['style'=>'width:60px;height:60px;'])?></td>
			<td><?= $sku?><?= Html::input('hidden','sku',$sku)?></td>
			<td><?= Html::textInput('qty',$qty , ['style'=>'width:50px'])?></td>
			<td><?= Html::textInput('location',@$ProductStock[$warehouse][$sku]['location_grid'], ['style'=>'width:50px'])?></td>
		</tr>
	<?php endforeach;?>
	
	</tbody>
	
  
</table>



<?php endforeach;?>