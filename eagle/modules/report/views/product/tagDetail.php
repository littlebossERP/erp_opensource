<?php
?>
<div>
<table cellspacing="0" cellpadding="0" width="100%" class="table table-hover" id="tagDetail-list-table">
<tr>
	<th nowrap><?php echo $tagSaleDetailData['isTagIDs']=="NO" ? "标签" : "序号"; ?></th><th nowrap>订单号</th><th nowrap>付款时间</th><th nowrap>SKU</th><th nowrap>产品名称</th>
	<th nowrap>品牌</th><th nowrap>销售数量</th><th nowrap>销售总价</th><th nowrap>销售平台</th><th nowrap>售往国家/地区</th>
</tr>
<?php 
	if(count($tagSaleDetailData['data']) > 0){
		foreach ($tagSaleDetailData['data'] as $tagDetails){
?>
<tr>
	<td nowrap><?php echo $tagSaleDetailData['isTagIDs']=="NO" ? $tagSaleDetailData['tag_name'] : $tagDetails['index']; ?></td><td nowrap><?=$tagDetails['order_id']; ?></td>
	<td nowrap><?=date ('Y-m-d H:i',$tagDetails['paid_time']); ?></td><td nowrap><?=$tagDetails['sku']; ?></td>
	<td nowrap><?=$tagDetails['product_name']; ?></td><td nowrap><?=$tagDetails['brand']; ?></td>
	<td nowrap><?=$tagDetails['quantity']; ?></td><td nowrap><?=$tagDetails['prices']; ?></td>
	<td nowrap><?=$tagDetails['order_source']; ?></td><td nowrap><?=$tagDetails['country']; ?></td>
</tr>
<?php
		}
	}
?>
</table>
</div>

<?php if($tagSaleDetailData['pagination']):?>
<div>
	<div id="tagDetail-list-pager" class="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$tagSaleDetailData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $tagSaleDetailData['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
</div>
<?php endif;?>

<?php 
// 该例子支持通过 ajax 更新table 和 pagination页面，启动该功能需要下方代码初始化js配置，以及给下面两个widget配置"isAjax"=>true,
$options = array();
$options['pagerId'] = 'tagDetail-list-pager';// 下方包裹 分页widget的id
$options['action'] = \Yii::$app->request->getPathInfo().
	"?tag_id=".$tagSaleDetailData['tag_id']."&tag_name=".$tagSaleDetailData['tag_name'].
	"&start=".$tagSaleDetailData['start']."&end=".$tagSaleDetailData['end'].
	"&source=".$tagSaleDetailData['source']."&site=".$tagSaleDetailData['site']; // ajax请求的 action
$options['page'] = $tagSaleDetailData['pagination']->getPage();// 当前页码
$options['per-page'] = $tagSaleDetailData['pagination']->getPageSize();// 当前page size
$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
$this->registerJs('$("#tagDetail-list-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
?>