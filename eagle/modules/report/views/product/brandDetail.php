<?php
?>
<div>
<table cellspacing="0" cellpadding="0" width="100%" class="table table-hover" id="brandDetail-list-table">
<tr>
	<th nowrap>品牌</th><th nowrap>订单号</th><th nowrap>付款时间</th><th nowrap>SKU</th><th nowrap>产品名称</th>
	<th nowrap>销售数量</th><th nowrap>销售总价</th><th nowrap>销售平台</th><th nowrap>售往国家/地区</th>
</tr>
<?php 
	if(count($brandSaleDetailData['data']) > 0){
		foreach ($brandSaleDetailData['data'] as $brandDetails){
?>
<tr>
	<td nowrap><?=$brandSaleDetailData['brand_name']; ?></td><td nowrap><?=$brandDetails['order_id']; ?></td>
	<td nowrap><?=date ('Y-m-d H:i',$brandDetails['paid_time']); ?></td><td nowrap><?=$brandDetails['sku']; ?></td>
	<td nowrap><?=$brandDetails['product_name']; ?></td>
	<td nowrap><?=$brandDetails['quantity']; ?></td><td nowrap><?=$brandDetails['prices']; ?></td>
	<td nowrap><?=$brandDetails['order_source']; ?></td><td nowrap><?=$brandDetails['country']; ?></td>
</tr>
<?php
		}
	}
?>
</table>
</div>

<?php if($brandSaleDetailData['pagination']):?>
<div>
	<div id="brandDetail-list-pager" class="pager-group">
	    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$brandSaleDetailData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $brandSaleDetailData['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
</div>
<?php endif;?>

<?php 
// 该例子支持通过 ajax 更新table 和 pagination页面，启动该功能需要下方代码初始化js配置，以及给下面两个widget配置"isAjax"=>true,
$options = array();
$options['pagerId'] = 'brandDetail-list-pager';// 下方包裹 分页widget的id
$options['action'] = \Yii::$app->request->getPathInfo().
	"?brand_id=".$brandSaleDetailData['brand_id']."&brand_name=".$brandSaleDetailData['brand_name'].
	"&start=".$brandSaleDetailData['start']."&end=".$brandSaleDetailData['end'].
	"&source=".$brandSaleDetailData['source']."&site=".$brandSaleDetailData['site']; // ajax请求的 action
$options['page'] = $brandSaleDetailData['pagination']->getPage();// 当前页码
$options['per-page'] = $brandSaleDetailData['pagination']->getPageSize();// 当前page size
$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
$this->registerJs('$("#brandDetail-list-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);
?>