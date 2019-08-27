<?php
use \yii\helpers\Html;
use \yii\helpers\ArrayHelper;
$this->registerCssFile ( \Yii::getAlias('@web') . '/css/listing-draft/wish-lists.css' );
$this->registerJsFile(\Yii::getAlias('@web') . "/js/project/listing-draft/wish_lists.js", ['depends' => ['eagle\assets\PublicAsset']]);
$pageBar = new \render\layout\Pagebar();
$pageBar->page = $page;

echo $this->render('//layouts/new/left_menu_2',$menu);
$this->title="wish搬家";
?>


<div class="content-wrapper">
	<div class="block">
		<h3>Wish搬家</h3>
	</div>
	<div class="table-action clearfix">
		<div class="pull-left"> 
			<form action="wish-lists" method="get" > 
				<?= Html::dropDownList('site_id',@$_GET['site_id'],ArrayHelper::map($shops->asArray()->all(),'site_id','store_name'),[	
					'prompt'=>'全部店铺',
					'class'=>'iv-input'
				]) ?>
				<!-- <select class="iv-input">
					<option value="">全部商品</option>
				</select> -->
				<div class="input-group iv-input">
					<select name="search-type" class="iv-input" aria-hidden="true">
						<option value="parent_sku">SKU</option>
						<option value="name">标题</option>
					</select>
					<input type="text" class="iv-input" placeholder="搜索内容" name='content-search' />
					<button type="submit" class="iv-btn btn-search">
						<span class="iconfont icon-sousuo"></span> 	
					</button>
				</div>	
			</form>		
		</div>
		<div class="pull-right">
			<a href="sync-start" target="_modal" class="iv-btn btn-important product-css" title="同步wish商品">同步产品</a>
		</div>	
	</div>
	<div class="clearfix">
		<div class="pull-left left-icon">
			<label>
				<a href="modal" target="_modal" title="选择店铺" param="$('#wish_list').serializeObject()">
					<span class="iconfont icon-yijianxiugaiyouxiaoqi"></span> 批量搬家
				</a>
			</label>
		</div>
		<div class="pull-right">
			<?= $pageBar ?>
		</div>
	</div>	
	<div class="block">
		<table id="wish_list" class="iv-table table-default middle-tab" cellpadding="0" cellspacing="0">
			<thead>
				<tr>
					<th>
						<input type="checkbox" id="goodIds" check-all="e1">
					</th>
					<th iv-tips="haha">图片</th>
					<th>标题</th>
					<th>SKU</th>
					<th iv-tips="haha">操作</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($products as $product): ?>
				<tr>
					<td class="checktd">
						<input type="checkbox" data-check="e1" name="parent_sku[]" value="<?= $product->parent_sku?>">
					</td>
					<td width="100" style="text-align:center;">
						<img src="<?= $product->main_image ?>" class="img-td2" alt="">
					</td>
					<td>
						<?= $product->name ?>
					</td>
					<td width="180"><?= $product->parent_sku ?></td>
					<td width="100">
						<a href="modal?parent_sku=<?= $product->parent_sku?>" target="_modal" title="选择店铺" class="iv-btn btn-success button_top">搬家到</a>
						<a class="iv-btn btn-default button_bottom" iv-tips data-url="view-draft-log?<?= http_build_query([
							'parent_sku'=>$product->parent_sku,
							'platform_from'=>'wish',
							'shop_from'=>$product->site_id
						]) ?>">搬家记录</a>
					</td>
				<?php endforeach; ?>
				</tr>
			</tbody>
			<tfoot>
				<tr><td colspan="7">
							<?= $pageBar ?>
		
					</td>
				</tr>
			</tfoot>
				<!-- <tr>
					<td colspan="7">
						<?= $pageBar ?>
					</td>
				</tr>
			</tfoot> -->
		</table>
	</div>
	
</div>






