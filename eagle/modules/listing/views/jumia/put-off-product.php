<?php 
use yii\helpers\Html;
use yii\widgets\LinkPager;
use eagle\helpers\HtmlHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\modules\listing\helpers\LazadaAutoFetchListingHelper;
$this->registerCssFile ( \Yii::getAlias('@web') . '/css/listing/jumiaListing.css?v='.eagle\modules\util\helpers\VersionHelper::$jumia_listing_version );
$this->registerJsFile(\Yii::getAlias('@web')."/js/project/listing/jumia_listing.js?v=".eagle\modules\util\helpers\VersionHelper::$jumia_listing_version, ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("jumiaListing.list_init()", \yii\web\View::POS_READY);
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
    'sku'=>'Sku'
];
$edit_type=[
    'price'=>'价格',
    'sale_message'=>'促销信息',
    'quantity'=>'库存'
];
// print_r($online_product);exit()
$this->title = TranslateHelper::t("Jumia下架商品列表");
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
</style>
<?php 
	$menu = LazadaApiHelper::getLeftMenuArr('jumia');
    echo $this->render('//layouts/new/left_menu_2',[
	'menu'=>$menu,
	'active'=>$activeMenu
	]);
?>
<div class="col2-layout jumia-listing jumia-listing-offline-product">
        <div class="search">
        <form action="/listing/jumia/off-shelf-product" method="POST" id="put_off_search">
            <?=Html::dropDownList('shop_name',@$_REQUEST['shop_name'],$shop_name,['onchange'=>"search_submit($(this).val());",'class'=>'eagle-form-control','id'=>'','style'=>'','prompt'=>'全部jumia店铺'])?>
            <br />
            <?=Html::dropDownList('condition',@$_REQUEST['condition'],$condition,['onchange'=>"",'class'=>'eagle-form-control','id'=>'','style'=>'padding-top:3px;'])?>
            <input type="text" class="eagle-form-control" id="condition_search" name="condition_search" value="<?php echo !empty($_REQUEST['condition_search'])?htmlentities($_REQUEST['condition_search']):null?>"><input type="submit" value="搜索"  id="search" class="btn btn-success btn-sm">
         	<input type="button" class="btn btn-default btn-sm" value="同步商品" data-toggle="modal" data-target="#Sync_product" onclick="jumiaListing.reset()">
            <input type="button" class="btn btn-default btn-sm" value="批量上架" onclick="jumiaListing.batchPutOn()">
        </form>
        </div>
        
        <div>
		<table class="table table-bordered">
			<thead>
				<tr>
					<th style="width: 50px"><span class="glyphicon product_all_show glyphicon-plus" style="cursor: pointer"></span><input type="checkbox" id="chk_all"></th>
					<th style="width: 100px">图片</th>
					<th style="width: 200px">标题</th>
					<th style="width: 125px">Parent SKU</th>
					<th style="width: 85px">库存</th>
					<th style="width: 115px">产品状态</th>
					<th style="width: 200px">店铺名称</th>
					<th style="width: 125px">修改状态</th>
					<th style="width: 50px">操作</th>
				</tr>
			 </thead>
			 <?php if(!empty($online_product)):?>
			 <tbody class="lzd_body">
			 <?php foreach ($online_product as $parent_sku_key => $val):?>
			 <?php 
			 // if(count($val)>1){//多个产品
			 // $total_quantity=0;//总数量
			 // if(isset($val[$parent_sku_key])){
				 // $parent_product = $val[$parent_sku_key];
			 // }else{
				 // foreach ($val as $childProduct){
					 // $parent_product = $childProduct;
					 // break;
				 // }
			 // }
			 // foreach ($val as $detail_product){
				// $total_quantity += $detail_product['Quantity'];
			 // }
				if(!empty($val['parent']['item'])){//判断是否parent产品
					$parent_product = $val['parent']['item'];
				}else{
					$parent_product = $val['items'][0];
				}
			 ?>
				<tr data-id="<?php echo $parent_product['id']?>" class="striped-row">
					<td style="width: 56px">
						<span class="glyphicon product_show glyphicon-plus" style="cursor: pointer"></span>
						<input type="checkbox" id="chk_one_<?php echo $parent_product['id']?>" name="parent_chk">
					</td>
					<td><img src="<?php echo $parent_product['MainImage'];?>" style="max-width:60px;max-height:60px;"></td>
					<td><?php echo $parent_product['Name'];?></td>
					<td><?php echo $parent_product['ParentSku'];?></td>
					<td class="table-font-weight"><?php echo $val['parent']['quantity'];?></td>
					<?php if(isset(LazadaAutoFetchListingHelper::$SUBSTATUS_KEY_NAME_MAP[$parent_product['sub_status']])):?>
					<td><?= LazadaAutoFetchListingHelper::$SUBSTATUS_KEY_NAME_MAP[$parent_product['sub_status']]?></td>
					<?php else :?>
					<td><?= $parent_product['sub_status']?></td>
					<?php endif;?>
					<td><?php echo !empty($val['parent']['lazada_uid_id'])?$shop_name[$val['parent']['lazada_uid_id']]:'';?></td>
					<?php if($parent_product['is_editing'] <> 0):?>
					<td><?= LazadaAutoFetchListingHelper::$LISTING_EDITING_TYPE_MAP[$parent_product['is_editing']]?></td>
					<?php else :?>
					<td style="word-break:break-all; <?php echo !empty($parent_product['error_message'])?'color:red;':'';?>">
						<?php if(!empty($parent_product['error_message'])):?>
							<?php if(!empty($parent_product['feed_id'])):?>
							<?= "上次修改错误：".$parent_product['error_message']?>
							<?php else :?>
							<?= "审核失败原因：".$parent_product['error_message']?>
							<?php endif;?>
						<?php endif;?>
					</td>
					<?php endif;?>
					<td class="table-operate-style">
					<a title="上架" onclick="jumiaListing.parentProductPutOn(this)"><span class="iconfont icon-piliangshangjia"></span></a>
					</td>
				</tr>
				<?php $num=0;foreach ($val['items'] as $detail_product):?>
				<tr class="detail_product product_<?php echo $parent_product['id']?> variation_tr <?php echo $num%2==1?"striped-row":null;$num++;?>" data-productid="<?php echo $detail_product['id']?>" style="display: none" >
					<td>
						<span class="glyphicon" style="width:12px"></span>
						<input type="checkbox" id="chk_one" name="productcheck" parentid="<?php echo $parent_product['id']?>" >
					</td>
					<td>尺寸：<?php echo $detail_product['Variation']?></td>
					<td>SKU：<?php echo $detail_product['SellerSku']?></td>
					<td>价格：<?php echo $detail_product['Price']?></td>
					<td>库存：<?php echo $detail_product['Quantity']?></td>
					<td>促销价：<?= (empty($detail_product['SalePrice']) || $detail_product['SalePrice'] == 0)?"-":$detail_product['SalePrice'] ?></td>
					<?php if(empty($detail_product['SaleStartDate']) && empty($detail_product['SaleEndDate'])):?>
					<td colspan="2">促销时间：-</td>
					<?php else :?>
					<td colspan="2">促销时间：<?= !empty($detail_product['SaleStartDate'])?date("Y-m-d",$detail_product['SaleStartDate']):""?>~<?= !empty($detail_product['SaleEndDate'])? date("Y-m-d",$detail_product['SaleEndDate']):""?></td>
					<?php endif;?>
					<td class="table-operate-style">
					<a title="上架" onclick="jumiaListing.productPutOn(<?php echo $detail_product['id']?>)"><span class="iconfont icon-piliangshangjia"></span></a>
					</td>
				</tr>
				<?php endforeach;?>
			 <?php endforeach;?>
			 </tbody>
			<?php endif;?>
		</table>
	</div>
        <div style="text-align: left;">
            <div class="btn-group" >
            	<?php echo LinkPager::widget(['pagination'=>$pages,'options'=>['class'=>'pagination']]);?>
        	</div>
                <?php echo \eagle\widgets\SizePager::widget(['pagination'=>$pages , 'pageSizeOptions'=>array( 5 , 10 , 20 , 50 ) , 'class'=>'btn-group dropup']);?>
        </div>
</div>
<input type="hidden" id="search_status" name="search_status" value="<?php echo !empty($search_status)?"search":"no_search";?>">
<!-- 同步 -->
<div class="modal fade" id="Sync_product" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel">商品同步</h4>
      </div>
      <form id="Sync-product">
      <div class="modal-body">
        <span style="padding-left:55px"><label for="Sync_lzd_uid">选择店铺：</label><?=Html::dropDownList('Sync_lzd_uid',@$_REQUEST['Sync_lzd_uid'],$shop_name,['onchange'=>"",'class'=>'eagle-form-control','id'=>'Sync_lzd_uid','style'=>'width:260px;','prompt'=>'请选择店铺'])?></span>
        <br /><span class="success_message" style="padding-left:55px;"></span> 
      </div>
      <div class="modal-footer">
        <input id="Sync_product_comfirm" value="确认" type="button" class="btn btn-primary" onclick="jumiaListing.SyncSubmit()">
        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
      </div>
      </form>
    </div>
  </div>
</div>
<script>
function search_submit(val){
	 $('form[id="put_off_search"]').submit();		   
}
</script>