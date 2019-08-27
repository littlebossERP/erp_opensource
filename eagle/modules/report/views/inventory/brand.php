<?php

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/report/inventory/brand.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("brand.init();" , \yii\web\View::POS_READY);

?>
<style>
.modal-dialog{
		width: 1000px;
}
</style>
<h4><label>品牌统计</label></h4>

<button class="btn btn-info" style="margin-bottom: 10px;" onclick="brand.exportExcel()">导出Excel</button>
<table cellspacing="0" cellpadding="0" width="100%" class="table table-hover">
<tr>
<th nowrap>序号</th><th nowrap>品牌</th><th nowrap>有库存SKU数量</th><th nowrap>商品数量</th><th nowrap>商品价值</th><th nowrap>操作</th>
<?php
	if(count($brandsData['data']) > 0){
		foreach ($brandsData['data'] as $brands){
?>
<tr>
	<td nowrap><?=$brands['id']; ?></td><td nowrap><?=$brands['name']; ?></td><td nowrap><?=$brands['sku']; ?></td><td nowrap><?=$brands['stock']; ?></td>
	<td nowrap><?=$brands['stock_value']; ?></td>
	<td nowrap><button onclick="brand.viewBrandInventoryDetail('<?=$brands['brand_id'] ?>','<?=$brands['name'] ?>')">显示明细</button></td>
</tr>
<?php 
		}
	}
?>
</tr>
</table>

 <?php if($brandsData['pagination']):?>
<div id="pager-group">
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$brandsData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $brandsData['pagination'],'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>