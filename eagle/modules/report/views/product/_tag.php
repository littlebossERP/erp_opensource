<?php

use eagle\components\DateSelectWidget;

$this->registerCssFile(\Yii::getAlias('@web').'/js/project/report/jquery-ui-1.10.4.custom.min.css' , ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
// $this->registerJsFile(\Yii::getAlias('@web')."/js/project/report/jquery-ui-1.10.4.custom.min.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/report/report.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/report/product/tag.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("tag.init();" , \yii\web\View::POS_READY);

?>

<style>
.modal-dialog{
		width: 1000px;
}
</style>

<table>
<tr>
	<td>
	请选择销售店
		<select id="report-product-tag-select">
			<option selected="selected" value="0-0">(全部)</option>
			<?php 
			if (count($shopArr) > 0){
				foreach ($shopArr as $shop){
			?>
			<option value='<?=$shop['sourceAndId'] ?>'><?=$shop['sourceAndId'] ?></option>
			<?php
				}
			}
			?>
		</select>
	</td>
	<td style="width: 600px;">
		<span style="margin-left: 10px;">
			<?php echo DateSelectWidget::widget(); ?>
		</span>
	</td>
	<td><button class="btn btn-info" style="margin-bottom: 10px;" onclick="tag.exportExcel()">导出Excel</button></td>
</tr>
</table>

<div>
<table cellspacing="0" cellpadding="0" width="100%" class="table table-hover" id="tag-list-table">
<tr>
<th>序号</th><th>标签</th><th>有销售SKU数量</th><th>销售商品数量</th><th>有销售品牌数量</th>
<th>销售总金额</th><th>操作</th>
</tr>
<?php
	if (count($tagSaleData['data']) > 0){
		foreach ($tagSaleData['data'] as $tagSales){
?>
<tr>
	<td><?=$tagSales['id'] ?></td><td><?=$tagSales['tag_name'] ?></td><td><?=$tagSales['skuSoldCount'] ?></td>
	<td><?=$tagSales['volume'] ?></td><td><?=$tagSales['brand'] ?></td><td><?=$tagSales['price'] ?></td>
	<td><button onclick="tag.viewTagSaleDetail('<?=$tagSales['tag_id'] ?>','<?=$tagSales['tag_name'] ?>')">显示明细</button></td>
</tr>
<?php 
		}
	}
?>

</table>
</div>

<?php if($tagSaleData['pagination']):?>
<div>
	<div id="tag-list-pager" class="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$tagSaleData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $tagSaleData['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
</div>
<?php endif;?>

<?php 
// 该例子支持通过 ajax 更新table 和 pagination页面，启动该功能需要下方代码初始化js配置，以及给下面两个widget配置"isAjax"=>true,
$options = array();
$options['pagerId'] = 'tag-list-pager';// 下方包裹 分页widget的id
$options['action'] = \Yii::$app->request->getPathInfo(); // ajax请求的 action
$options['page'] = $tagSaleData['pagination']->getPage();// 当前页码
$options['per-page'] = $tagSaleData['pagination']->getPageSize();// 当前page size
$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
$this->registerJs('$("#tag-list-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
?>

