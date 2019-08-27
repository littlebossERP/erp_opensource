<?php 
use yii\helpers\Html;
use eagle\modules\listing\models\AliexpressListing;

$this->registerJsFile(\Yii::getAlias('@web').'/js/project/listing/aliexpress.js',['depends'=>'eagle\assets\PublicAsset']);
$this->registerJs("$.aliexpress.initAliexpressList()",\yii\web\View::POS_READY);
$this->title = '速卖通平台商品';
echo $this->render('//layouts/new/left_menu_2',$menu);

$labels = [
	'auditing',
	'editingRequired',
	'offline',
	'onSelling'
];
$labelKey = array_flip(AliexpressListing::$product_status);

$syncBtn = '<a href="/listing/aliexpress/sync-start" class="iv-btn btn-important" target="_modal" title="同步商品">同步商品</a>';

if(!isset($_GET['product_status'])){
	$_GET['product_status'] = 3;
}

?>

<style>
.product-list-img{
	max-width: 80px;
	max-height: 80px;
}
.btn-together{
	padding-top: 7px;
    padding-left: 10px;
    padding-right: 10px;
    padding-bottom: 7px;
    line-height: 1;
    border: 0;
	display: inline-block;
	vertical-align: middle;
}	
.edit_tips,.edit_tips:hover{
	background-color: #FF9600;
    color: white;
    display: block;
    width: 50px;
    text-align:center;
    -webkit-user-select:none;
    -moz-user-select:none;
    -ms-user-select:none;
    user-select:none;

}
</style>

<form action="" class="block">
	<div class="main-tab">
		<?php foreach($labels as $label): ?>
		<label>
			<input type="radio" name="product_status" value="<?= $labelKey[$label] ?>" <?= (@$_GET['product_status']==$labelKey[$label]?'checked':'') ?> /> 
			<span>
				<?= AliexpressListing::$product_status_label[$label] ?>（<?= $statusTotal[$label] ?>）
			</span>
		</label>
		<?php endforeach; ?>
	</div>
	<div class="block clearfix">
		<div class="pull-left">
			<?= Html::dropDownList('sellerloginid',@$_GET['sellerloginid'],$shops,[
				'prompt'=>'全部速卖通店铺',
				'class'=>'iv-input filter'
			]) ?>
			<?= Html::dropDownList('freight',@$_GET['freight'],$freight,[
				'prompt'=>'运费模板',
				'class'=>'iv-input filter'
			]) ?>
			<div class="input-group iv-input">
				<?= Html::dropDownList('search',@$_GET['search'],$search,[
					'class'=>'iv-input'
				]) ?>
				<input type="text" class="iv-input" name="v" value="<?= @$_GET['v'] ?>" />
				<button type="submit" class="iv-btn btn-search">
					<span class="iconfont icon-sousuo"></span>
				</button>
			</div>
		</div>
		<div class="pull-right">
			<?= (!isset($_GET['product_status']) || $_GET['product_status']==3) ? $syncBtn:'' ?>
		</div>
	</div>
</form>
<?php if(isset($_GET['product_status']) && $_GET['product_status']!=3 && $_GET['product_status'] != 4): ?>
<div class="table-action clearfix">
	<div class="pull-left">
		<!-- <a href="#">
			<span class="iconfont icon-shanchu"></span>
			批量删除
		</a> -->
		<?php if(false){?>
			<a href="/listing/aliexpress/batch-edit-brand" param="$('#onlineList').serializeObject()" target="_modal" method="post">
				<span class="iconfont icon-kebianji"></span>
				批量修改品牌
			</a>
			<?php if(@$_GET['product_status']==1): ?>
			<a class="batchEnable" target="_request" href="/listing/aliexpress/batch-enable?on=" param="$('#onlineList').serializeObject()" method="post">
				<span class="iconfont icon-piliangxiajia"></span>
				批量下架
			</a>
			<?php endif;if(@$_GET['product_status']==2): ?>
			<a class="batchEnable" target="_request" href="/listing/aliexpress/batch-enable?on=1" param="$('#onlineList').serializeObject()" method="post">
				<span class="iconfont icon-piliangshangjia"></span>
				批量上架
			</a>
			<?php endif; ?>
		<?php }?>
	</div>
	<div class="pull-right">
		<?= $syncBtn ?>
	</div>
</div>
<?php endif; 
$pageBar = new \render\layout\Pagebar();
$pageBar->page = $page;
?>
<div class="block clearfix">
	<div class="pull-left">
		<?= $pageBar ?>
	</div>
</div>
<table id="onlineList" class="iv-table block table-default">
	<thead>
		<tr>
			<th width="80">
				<input type="checkbox" check-all="product"  />
			</th>
			<th width="80">图片</th>
			<th style="max-width:520px;">产品标题</th>
			<th>产品组</th>
			<th>售价（USD）</th>
			<th>运费模板</th>
			<th>店铺</th>
			<th>创建时间</th>
			<?php 
				$width = $product_status != 4 ? 'min-width:130px' : 'min-width:60px';
				if($product_status != 3 && $product_status != 4):
			?>
			<th style="<?=$width?>">操作</th>
			<?php endif;?>
		</tr>
	</thead>
	<tbody>
		<?php foreach($products->each(1) as $product): 
		// var_dump($product->onlineDetail->groups);
		// if(!$product->onlineDetail){
		// 	var_dump($product->productid);
		// }
		// echo '<br><br><br>';continue;
		?>
		<tr>
			<td class="checktd">
				<input type="checkbox" data-check="product" name="productid[]" value="<?= $product->productid ?>" />
			</td>
			<td>
				<img class="product-list-img" qtip src="<?= $product->photo_primary ?>" alt="" />
			</td>
			<td style="word-break:break-all;">
				<?= $product->subject ?>
				<!-- <?php //if($product->edit_status == 2):
					// 	echo '<a class="edit_tips" title="正在将修改信息提交Aliexpress,请耐心等待">修改中</a>';
					// endif;
				?> -->
			</td>
			<td>
				<?= $product->onlineDetail?implode(', ',array_map(function($group){
						return $group->group_name;
					},$product->onlineDetail->groups)):'' ?>
			</td>
			<td>
				<?=json_decode($product->onlineDetail->aeopAeProductSKUs)[0]->skuPrice ?>
			</td>
			<td>
				<?= $product->freight?$product->freight->template_name:'' ?>
			</td>
			<td>
				<?= $product->selleruserid  ?>
			</td>
			<td>
				<?= $product->createDateTime ?>
			</td>
			<?php if($product_status !=3 && $product_status != 4): ?>
			<td>
				<?php if(false){?>
					<a class="btn btn-together btn-success" href="/listing/aliexpress/online-edit?product_id=<?=$product->productid?>" target="_blank" style="margin:0 3px;">编辑</a>
					<?php if($product_status == 1):?>
					<a class="btn btn-together btn-success productEnable" data-on="0" data-productid="<?=$product->productid?>">下架</a> 
					<?php elseif($product_status == 2):?>
					<a class="btn btn-together btn-success productEnable" data-on="1" data-productid="<?=$product->productid?>">上架</a> 
					<?php endif;?>
				<?php }?>
			</td>
			<?php endif;?>
		</tr>
		<?php endforeach; ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="9"><?= $pageBar ?></td>
		</tr>
		
	</tfoot>
</table>