<?php 
use yii\helpers\Html;
 ?>

<style>
.batch-edit-brand td{
	padding:10px;
}
.batch-edit-brand .brand-label{
	text-align: right;
}
</style>
<form ajax-form>
	<table class="batch-edit-brand">
		<?php foreach($categories as $cate_id=>$cate): ?>
		<tr>
			<td class="brand-label">
				<?= $cate['name'] ?> ( <?= count($cate['productid']) ?> )
				<?php 
				foreach($cate['productid'] as $productid){
					echo "<input type='checkbox' name='productid[{$cate_id}][]' value='{$productid}' checked='checked' style='display:none;' />";
				}
				?>
			</td>
			<td>
			<?php
			if(!$cate['option']){
				echo "该类目不支持品牌选择";
			}else{
				echo Html::dropDownList('brand['.$cate_id.']',NULL,$cate['option'],[
					'prompt'=>'选择此分类品牌',
					// 'class'=>'iv-input'
				]);
			}
			?>
			</td>
		</tr>
		<?php endforeach; ?>
	</table>

	<div class="block text-center">
		<a type="submit" class="iv-btn btn-success modal-close" method="post" action="batch-edit-brand-exec">确定</a>
	</div>
</form>