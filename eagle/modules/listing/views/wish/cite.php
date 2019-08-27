<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\widgets\ESort;
$attributes = ['parent_sku','name','main_image','create_time'];
$sort = new ESort(['isAjax'=>true,'attributes'=>$attributes]);

//初始化js配置
$options = array();
$options['pagerId'] = 'wish-goods-list-pager';// 包裹 分页widget的id
$options['action'] = \Yii::$app->request->getPathInfo(); // ajax请求的 action
// 当前页码，为保证当前页码与打开的页面一致，
// 这里页码通过后台初始化的Pagination对象的getPage() 获得。
$options['page'] = $WishFanbenModel['pagination']->getPage();
// 当前page size ，为保证当前页码与打开的页面一致， 
// 这里页码通过后台初始化的Pagination对象的getPageSize() 获得。
$options['site_id'] = $site_id;
if(isset($search_key)){
	$options['select_status'] =$select_status;
	$options['search_key'] = $search_key;
}
$options['per-page'] = $WishFanbenModel['pagination']->getPageSize();
$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
$this->registerJsFile(\Yii::getAlias('@web').'/js/project/listing/wish_cite.js',['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs('$("#cite_goods_list_table").initGetPageEvents('.json_encode($options).')' , \Yii\web\View::POS_READY);
?>
<div class="form-group cite_goods_list">
	<div class="col-xs-8">
		<table class="table table-bordered table-hover mTop20" id="cite_goods_list_table">
			<tr>
				<th></th>
				<th><?=$sort->link('main_image',['label'=>TranslateHelper::t('缩略图')])?></th>
				<th><?=$sort->link('name',['label'=>TranslateHelper::t('标题')])?></th>
				<th><?=$sort->link('parent_sku',['label'=>TranslateHelper::t('SKU')])?></th>
			</tr>
			<?php foreach($WishFanbenModel['data'] as $key => $val): ?>
				<tr>
					<td><input type="radio" name="cite_goods_id" value="<?=$val['id']?>"></td>
					<td><img src="<?=$val['main_image'];?>" width="50"></td>
					<td><?=$val['name']?></td>
					<td style="text-align:center"><?=$val['parent_sku']?></td>
				</tr>
			<?php endforeach;?>
		</table>
		<?php if($WishFanbenModel['pagination']):?>
			<div>
				<div id="wish-goods-list-pager" style="clear:both;float:left;width:100%;">
				    <div class="btn-group" style="width: 49.6%;text-align: right;">
				    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $WishFanbenModel['pagination'],'options'=>['class'=>'pagination']]);?>
					</div>
				    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$WishFanbenModel['pagination'] , 'pageSizeOptions'=>array( 5 ,10,20,30,40,50) , 'class'=>'btn-group dropup']);?>
				</div>
			</div>
		<?php endif;?>
	</div>
</div>
