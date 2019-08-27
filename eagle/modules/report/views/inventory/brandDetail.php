<?php
?>
<div>
<table cellspacing="0" cellpadding="0" width="100%" class="table table-hover" id='brandDetail-list-table'>
<tr>
	<th nowrap>品牌</th><th nowrap>SKU</th><th nowrap>产品名称</th><th nowrap>库存数量</th><th nowrap>库存价值</th>
</tr>
<?php 
	if (count($brandDetailData['data']) > 0){
		foreach ($brandDetailData['data'] as $brandDetails){
?>
<tr>
	<td nowrap><?=$brandDetailData['brand_name'] ?></td><td nowrap><?=$brandDetails['sku'] ?></td>
	<td nowrap><?=$brandDetails['name'] ?></td>
	<td nowrap><?=$brandDetails['stock'] ?></td><td nowrap><?=$brandDetails['stock_value'] ?></td>
</tr>
<?php
		}
	}
?>
</table>
</div>

<?php if($brandDetailData['pagination']):?>
<div>
	<div id="brandDetail-list-pager" class="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$brandDetailData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $brandDetailData['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
</div>
<?php endif;?>

<?php 
// 该例子支持通过 ajax 更新table 和 pagination页面，启动该功能需要下方代码初始化js配置，以及给下面两个widget配置"isAjax"=>true,
$options = array();
$options['pagerId'] = 'brandDetail-list-pager';// 下方包裹 分页widget的id
$options['action'] = \Yii::$app->request->getPathInfo()."?brand_id=".$brandDetailData['brand_id']."&brand_name=".$brandDetailData['brand_name']; // ajax请求的 action
$options['page'] = $brandDetailData['pagination']->getPage();// 当前页码
$options['per-page'] = $brandDetailData['pagination']->getPageSize();// 当前page size
$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
$this->registerJs('$("#brandDetail-list-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
?>