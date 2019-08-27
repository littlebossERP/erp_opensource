<?php
?>
<div>
<table cellspacing="0" cellpadding="0" width="100%" class="table table-hover" id="tagDetail-list-table">
<tr>
	<th nowrap>标签</th><th nowrap>SKU</th><th nowrap>产品名称</th><th nowrap>库存数量</th><th nowrap>库存价值</th>
</tr>
<?php 
	if(count($tagDetailData['data']) > 0){
		foreach ($tagDetailData['data'] as $tagDetails){
?>
<tr>
	<td nowrap><?=$tagDetailData['tag_name']; ?></td><td nowrap><?=$tagDetails['sku']; ?></td>
	<td nowrap><?=$tagDetails['name']; ?></td>
	<td nowrap><?=$tagDetails['stock']; ?></td><td nowrap><?=$tagDetails['stock_value']; ?></td>
</tr>
<?php
		}
	}
?>
</table>
</div>

<?php if($tagDetailData['pagination']):?>
<div>
	<div id="tagDetail-list-pager" class="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$tagDetailData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $tagDetailData['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
</div>
<?php endif;?>

<?php 
// 该例子支持通过 ajax 更新table 和 pagination页面，启动该功能需要下方代码初始化js配置，以及给下面两个widget配置"isAjax"=>true,
$options = array();
$options['pagerId'] = 'tagDetail-list-pager';// 下方包裹 分页widget的id
$options['action'] = \Yii::$app->request->getPathInfo()."?tag_id=".$tagDetailData['tag_id']."&tag_name=".$tagDetailData['tag_name']; // ajax请求的 action
$options['page'] = $tagDetailData['pagination']->getPage();// 当前页码
$options['per-page'] = $tagDetailData['pagination']->getPageSize();// 当前page size
$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
$this->registerJs('$("#tagDetail-list-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
?>