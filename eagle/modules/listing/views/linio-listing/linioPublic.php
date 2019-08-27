<?php 
use yii\helpers\Html;
use yii\widgets\LinkPager;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\HtmlHelper;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/linio_listing.js?v=".eagle\modules\util\helpers\VersionHelper::$linio_listing_version, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("linioListing.list_init()", \yii\web\View::POS_READY);
// $station=[
//   'MY'=>'MY', 
//   'TL'=>'TL'
// ];
// $shop_name=[
//     '1'=>'123@qq.com',
//     '2'=>'321@qq.com'
// ];
$condition=[
    'title'=>'标题',
    'sku'=>'SKU'
];
$batch=[
    '1'=>'批量发布',
    '2'=>'批量删除'
];

$this->title = TranslateHelper::t("Linio待发布列表");

?>
<style>
.table td{
	border:1px solid #d9effc !important;
	text-align:center;
	vertical-align: middle !important;
}
.table th{
	text-align:center;
	vertical-align: middle !important;
}
.table_in td{
	border:0px !important;
}
</style>
<?php 
	$menu = LazadaApiHelper::getLeftMenuArr('linio');
    echo $this->render('//layouts/new/left_menu_2',[
	'menu'=>$menu,
	'active'=>$activeMenu
	]);
?>
<div class="linio-listing linio-listing-publish">
	<div class="search">
	<form action="/listing/linio-listing/publish" method="GET">
		<?=Html::dropDownList('shop_name',@$_REQUEST['shop_name'],$shop_name,['onchange'=>"linio_submit($(this).val());",'class'=>'eagle-form-control','id'=>'','style'=>'','prompt'=>'全部linio店铺'])?>
		<br />
		<?=Html::dropDownList('condition',@$_REQUEST['condition'],$condition,['onchange'=>"",'class'=>'eagle-form-control','id'=>'','style'=>'width:90px;padding-top:3px;'])?>
		<input type="text" class="eagle-form-control" id="condition_search" name="condition_search" value="<?php echo !empty($_REQUEST['condition_search'])?htmlentities($_REQUEST['condition_search']):null?>"><input type="submit" value="搜索"  id="search" class="btn btn-success btn-sm">
		<?=Html::dropDownList('batch',@$_REQUEST['batch'],$batch,['onchange'=>"linioListing.batch(this)",'class'=>'eagle-form-control','id'=>'','style'=>'width:90px;padding-top:3px;','prompt'=>'批量操作'])?>
		<button type="button" class="btn btn-warning btn-sm" onclick="window.location.href='/listing/linio-listing/create-product'"><span class="glyphicon glyphicon-plus"></span>新建产品</button>
		<button type="button" class="btn btn-warning btn-sm" onclick="window.location.href='/listing/linio-listing/muti-site-publish'"><span class="glyphicon glyphicon-plus"></span>多站点发布商品</button>
		</form>
	</div>
	<table class="table table-bordered">
		<thead>
			<tr>
				<th><input type="checkbox" id="chk_all"></th>
				<th>图片</th>
				<th>标题</th>
				<th style="width: 120px;">SKU</th>
				<th style="width: 70px;">价格</th>
				<th>库存</th>
				<th>linio店铺</th>
				<th>创建时间</th>
				<th nowrap style="width:175px;">操作</th>
			</tr>
		 </thead>
		 <tbody class="lzd_body">
		 <?php $num=1;foreach ($data as $data_detail):?>
			<tr data-id="<?php echo $data_detail['id']?>" <?php echo $num%2==0?"class='striped-row'":null;$num++;?>>
				<td><input type="checkbox" id="chk_one"></td>
				<td><img src="<?php echo $data_detail['image'];?>" style="max-width:60px;max-height:60px;"></td>
				<td style="word-break:break-all;"><?php echo $data_detail['title'];?></td>
				<td colspan="3">
					<table style="width: 100%;"  class="table_in">
						<tbody>
							<?php foreach ($data_detail['variation'] as $var):?>
							<tr>
								<td style="width: 120px;"><?php echo $var['sku'];?></td>
								<td style="width: 70px;"><?php echo $var['price'];?></td>
								<td><?php echo $var['quantity'];?></td>
							</tr>
							<?php endforeach;?>
						</tbody>
					</table>
				</td>
				<td><?php echo $data_detail['shop_name'];?></td>
				<td><?php echo $data_detail['create_time'];?></td>
				<td>
					
					<a class="btn btn-default btn-xs"  href="/listing/linio-listing/copy-product?id=<?php echo $data_detail['id'];?>">复制为单站点发布</a>
					<br><a class="btn btn-default btn-xs"  href="/listing/linio-listing/copy-to-muti-site?id=<?php echo $data_detail['id'];?>">复制为多站点发布</a> 
					<br><a class="btn btn-default btn-xs" href="/listing/linio-listing/edit-product?id=<?php echo $data_detail['id'];?>">编辑</a>
					<input class="btn btn-default btn-xs" type="button" value="发布"  onclick="linioListing.publishOne(<?= $data_detail['id'];?>)">
					<input class="btn btn-default btn-xs" type="button" value="删除"  onclick="linioListing.deleteProduct(<?= $data_detail['id'];?>)">
				</td>
			</tr>
		 <?php endforeach;?>
		 </tbody>
		
	</table>
	<div style="text-align: left;">
		<div class="btn-group" >
			<?php echo LinkPager::widget(['pagination'=>$pages,'options'=>['class'=>'pagination']]);?>
		</div>
			<?php echo \eagle\widgets\SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 10 , 20 , 50 ) , 'class'=>'btn-group dropup']);?>
	</div>
</div>
<script>
function linio_submit(val){
	 $('form[method="GET"]').submit();		   
	}
function uid_submit(){
	$('form[id="create-product"]').submit();
}

</script>