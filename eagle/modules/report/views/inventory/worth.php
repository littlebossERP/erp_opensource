<?php

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/report/inventory/worth.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("worth.init();" , \yii\web\View::POS_READY);

?>
<h4><label>商品数量及价值</label></h4>

<form id="form-select-worth-warehouse" method="post">
请选择仓库：
<select id="report-inventory-worth-warehouse-select" name="whID">
	<option value="0" <?php if(!empty($_POST['whID'])) {if($_POST['whID']==0) echo 'selected';} ?>>(全部)</option>
<?php
	if(count($warehouseArr) > 0){
		foreach ($warehouseArr as $warehouse){
?>
	<option value='<?=$warehouse['warehouse_id'] ?>' <?php if(!empty($_POST['whID'])) {if($_POST['whID']==$warehouse['warehouse_id']) echo 'selected';} ?> ><?=$warehouse['name'] ?></option>
<?php 
		}
	}
?>	
</select>

<button type="button" class="btn btn-info" style="margin-left: 30px;" onclick="worth.exportExcel()">导出Excel</button>
</form>

<div>
<table id="worth-list-table" style="margin-top: 10px;" cellspacing="0" cellpadding="0" width="100%" class="table table-hover">
<tr>
	<th nowrap>排名</th><th nowrap>SKU</th><th nowrap>商品名称</th><th nowrap>商品数量</th><th nowrap>商品价值</th><th nowrap>仓库</th>
</tr>
<?php 
	if(count($productInventory['data']) > 0){
		foreach($productInventory['data'] as $productInventorys){
?>
	<tr>
		<td nowrap><?=$productInventorys['index'] ?></td><td nowrap><?=$productInventorys['sku'] ?></td>
		<td nowrap><?=$productInventorys['name'] ?></td><td nowrap><?=$productInventorys['stock'] ?></td>
		<td nowrap><?=$productInventorys['prices'] ?></td><td nowrap><?=$productInventorys['wh_name'] ?></td>
	</tr>
<?php 
		}
	}
?>
</table>
</div>

<?php if($productInventory['pagination']):?>
<div>
	<div id="purchase-list-pager" class="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$productInventory['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $productInventory['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
</div>
<?php endif;?>

<?php 
// 该例子支持通过 ajax 更新table 和 pagination页面，启动该功能需要下方代码初始化js配置，以及给下面两个widget配置"isAjax"=>true,
$options = array();
$options['pagerId'] = 'purchase-list-pager';// 下方包裹 分页widget的id
$options['action'] = \Yii::$app->request->getPathInfo(); // ajax请求的 action
$options['page'] = $productInventory['pagination']->getPage();// 当前页码
$options['per-page'] = $productInventory['pagination']->getPageSize();// 当前page size
$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
$this->registerJs('$("#worth-list-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
?>

