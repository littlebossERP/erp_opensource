<?php

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/report/inventory/tag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("tag.init();" , \yii\web\View::POS_READY);

?>
<style>
.modal-dialog{
		width: 1000px;
}
</style>

<h4><label>标签统计</label></h4>

<button class="btn btn-info" style="margin-bottom: 10px;" onclick="tag.exportExcel()">导出Excel</button>


<table cellspacing="0" cellpadding="0" width="100%" class="table table-hover">
<tr>
	<th nowrap>序号</th><th nowrap>标签</th><th nowrap>有库存SKU数量</th><th nowrap>商品数量</th>
	<th nowrap>有库存品牌数量</th><th nowrap>商品价值</th><th nowrap>操作</th>
</tr>
<?php
	if(count($allTagData['data']) > 0){
		foreach ($allTagData['data'] as $allTagDatas){
?>
	<tr>
		<td nowrap><?=$allTagDatas['id'] ?></td><td nowrap><?=$allTagDatas['tag_name'] ?></td><td nowrap><?=$allTagDatas['sku'] ?></td>
		<td nowrap><?=$allTagDatas['stock'] ?></td><td nowrap><?=$allTagDatas['brands'] ?></td><td nowrap><?=$allTagDatas['stock_value'] ?></td>
		<td nowrap><button onclick="tag.viewTagInventoryDetail('<?=$allTagDatas['tag_id'] ?>','<?=$allTagDatas['tag_name'] ?>')">显示明细</button></td>
	</tr>
<?php 
		}
	}
?>
</table>

 <?php if($allTagData['pagination']):?>
<div id="pager-group">
    <?= \eagle\widgets\SizePager::widget(['pagination'=>$allTagData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
    <div class="btn-group" style="width: 49.6%;text-align: right;">
    	<?=\yii\widgets\LinkPager::widget(['pagination' => $allTagData['pagination'],'options'=>['class'=>'pagination']]);?>
	</div>
</div>
<?php endif;?>