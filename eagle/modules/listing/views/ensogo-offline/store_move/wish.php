<?php 
$this->title = "wish搬家";

$this->registerJsFile ( \Yii::getAlias('@web') . '/js/project/listing/ensogo.js',['depends' => ['eagle\assets\PublicAsset']]);


echo $this->render('//layouts/new/left_menu',[
	'menu' => $menu,
	'active' => $active
]);



// var_dump(count($data['product_list']));die;

// foreach($data['product_list'] as $product){
// 	var_dump($product['tags']);
// }
// die;
?>
<style>
.tags a{
	margin-right:5px;
	margin-bottom:8px;
	display: inline-block;
}
.iv-table tr td[rowspan]{
	border-right:none;
}

.iv-table tr td[rowspan] + td{
	border-left:1px solid #ccc;
}
.iv-table td[colspan] span[data-variant]{
	margin:0 20px;
}
.tmp{
	display: none;
}
.pull-right{
	float: right;
}
</style>

<?php  
$site_id = \Yii::$app->request->get('site_id',0);

$store_name = [];

?>

<form method="get" action="" class="block iv-form form-horizontal">
	<div class="form-group">
		<div class="row">
			<a class="pull-right" style="color:red;font-size:13px;" href="http://www.littleboss.com/announce_info_21.html" target="_blank"><span class="glyphicon glyphicon-question-sign"></span> 搬家教程</a>
		</div>
	</div>
	<div class="form-group">
		<label for="" class="row">选择店铺：</label>
		<div class="row">
			<div class="input-control">
                <input type="hidden" id="hide_site_id" value="<?=$site_id;?>"/>
				<select name="site_id" class="iv-input" placeholder="请选择店铺" id="select-move-store">
					<option value="0">请选择店铺</option>
					<?php 
					$opt = [];
					foreach($data['account_list'] as $account){
						$opt[$account['site_id']] = $account['store_name'];
					}
					echo $options($opt,$site_id);
					?>
				</select>
				<a id="topush" target="_modal" href="multi-push-confirm?site_id=<?=$request->get('site_id') ?>" class="iv-btn btn-primary" title="批量发布">批量发布</a>
			</div>
		</div>
	</div>
	
	<div class="form-group">
		<label for="" class="row">产品标签：</label>
		<div class="row">
			<div class="tags">
				<?php 
				$i = 0;
				if(isset($data['tags_list']) && is_array($data['tags_list'])):
				foreach($data['tags_list'] as $name=>$count):
					if($i++ > 10){
						break;
					}
				?>
				<a class="select-tags <?php if($name == $data['tags_name']):?>active<?php endif;?>" trigger="$(this).find('input')"  >
					<?=$name ?>
					<span class="count"><?=$count ?></span>
					<input class="hidden" type="checkbox" name="tag" value="<?=$name ?>" />
				</a>
				<?php endforeach;endif;
				if(count($data['tags_list']) > 10): ?>
				<a target="#tags_all" class="select-tags" title="选择产品标签" >
					其他标签
				</a>
				<?php endif; ?>
			</div>
		</div>
	</div>

</form>

<div class="block" style="text-align:right;">
	由于ensogo需要从其他平台读取图片，搬家商品图片一段时间不会显示
</div>
<div class="block">
	
	<table class="iv-table table-default" id="store-move" data-url="get-wish-all-product">
		<thead>
			<tr>
				<th width="20">
					<input type="checkbox" check-all="product_id">
				</th>
				<th width="100">缩略图</th>
				<th>标题</th>
				<th>SKU</th>
				<th width="80">店铺</th>
				<th width="80">商品状态</th>
			</tr>
		</thead>
		<tbody>
			<?php 
			if(isset($data['product_list']) && is_array($data['product_list'])):
			foreach($data['product_list'] as $product): ?>
			<tr>
				<td class="checktd" style="text-align:center;">
					<input type="checkbox" data-check="product_id" data-name="product_id" name="product_id" value="<?= $product['wish_product_id']?>" />
					<input type="hidden" class="data-tags" value="<?= $product['tags']?>" />
				</td>
				<td style="text-align:center;">
					<img lazy-src="<?= $product['main_image'] ?>" alt="" data-name="main_image" style="max-height:80px;max-width:100px;">
				</td>
				<td data-name="name">
					<?= $product['name'] ?>
				</td>
				<td data-name="parent_sku"><?= $product['parent_sku'] ?></td>
				<td data-name="store_name"><?= $opt[$product['site_id']] ?></td>
				<td data-name="status" style="text-align:center;">已批准</td>
			</tr>
			<?php endforeach;endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<td colspan="6"></td>
			</tr>
		</tfoot>
	</table>

</div>

<?php if(!empty($data['page'])):?>
    <div id="pager-group" >
        <div class="btn-group" style="width: 49.6%;text-align: right;">
            <?=\yii\widgets\LinkPager::widget(['pagination' => $data['page'],'options'=>['class'=>'pagination']]);?>
        </div>
        <?= \eagle\widgets\SizePager::widget(['pagination'=>$data['page'] , 'pageSizeOptions'=>array( 200 ) , 'class'=>'btn-group dropup']);?>
    </div>
<?php endif;?>

<div id="tags_all" style='display:none;'>
	<form class="tags">
		<?php 
		if(isset($data['tags_list']) && is_array($data['tags_list'])):
		$i = 0;
		foreach($data['tags_list'] as $name=>$count):
			if($i++ <= 10){
				continue;
			}
		?>
		<a class="select-tags" trigger="$(this).find('input')" >
			<?=$name ?>
			<span class="count"><?=$count ?></span>
			<input class="hidden" type="checkbox" name="tag" value="<?=$name ?>" />
		</a>
		<?php endforeach;endif; ?>
	</form>
	<div class="block middle">
		<button class="iv-btn btn-success modal-close" >确定</button>
	</div>
</div>


