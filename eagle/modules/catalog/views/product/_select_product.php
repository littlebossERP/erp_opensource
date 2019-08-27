<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\widgets\ESort;

$attributes = ['sku','name','type','status','brand_id','supplier_id','purchase_by','prod_weight','update_time'];
$sort = new ESort(['isAjax'=>true,'attributes' => $attributes ]);

?>
<style>
.select_product .modal-body {
	max-height: 500px;
	overflow-y: auto;
}

.select_product .modal-dialog {
	width: 950px;
}

.pageSize-dropdown-div.btn-group.dropup {
  width: 49%;
}

#select-product-table td,th{
	border: 1px solid rgb(202,202,202);
	vertical-align: middle;
}
</style>

<script type="text/javascript">
</script>
<div>
	<div class="form-group" style="margin-bottom:5px;clear:both;float:left;width:100%;">
		<div class="dropdown" style="float: left; margin-right: 20px;">
			<button class="btn eagle-form-control" style="margin: 0px; width: 150px; text-align: right; " type="button" data-toggle="dropdown" aria-expanded="true">
				<span id="search_class_id" class_id="" style="float: left; ">所有分类</span>
				<span class="caret"></span>
			</button>
			<ul class="dropdown-menu">
				<li style="font-size: 12px;"><a class="changeClass" class_id="">|- 所有分类</a></li>
				<li style="font-size: 12px; margin-left: 15px;"><a class="changeClass" class_id="0">|- 未分类</a></li>
				<?php foreach($classData as $class){?>
				<li style="font-size: 12px; <?= 'margin-left:'.((int)(strlen($class['number']) / 2) * 15).'px'; ?>"><a class="changeClass" class_id="<?= $class['ID'] ?>">|- <?= $class['name'] ?></a></li>
				<?php }?>
			</ul>
		</div>
		<input id="select_product_search" class="eagle-form-control" value='' placeholder="<?= TranslateHelper::t('模糊搜索单个sku或名称；或批量搜索多个精确sku。多个sku之间用 ; 隔开')?>" style="float:left;margin:0px;width:450px;"/>
		<button type="button" id="btn_select_product_search"  class="btn btn-default" style="float:left;padding:0px;height:28px;width:30px;border-radius:0px;border:1px solid #b9d6e8;">
			<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
	    </button>
	</div>
	
	<div style="width:100%;float:left;">
		<table id="select-product-table" cellspacing="0" cellpadding="0" style="width=100%;font-size:12px;float:left;" class="table table-hover">
		<tr>
			<th style="width:20px;">
				<input id="chk_select_product_check_all" type="checkbox" value="all" aria-label="..."">
			</th>
			<th nowrap style="width:65px;"><?=TranslateHelper::t('商品图片') ?></th>
			<th nowrap ><?=$sort->link('sku',['label'=>TranslateHelper::t('SKU')]) ?></th>
			<th nowrap style="width:300px;"><?=$sort->link('name',['label'=>TranslateHelper::t('商品名称')])?></th>
			<!--  
			<th nowrap style="width:80px;"><?=$sort->link('status',['label'=>TranslateHelper::t('状态')])?></th>
			 -->
			<th nowrap style="width:100px;"><?=$sort->link('brand_id',['label'=>TranslateHelper::t('分类')])?></th>
			<th nowrap style="width:100px;"><?=$sort->link('supplier_id',['label'=>TranslateHelper::t('首选供应商')]) ?></th>
			<th nowrap style="width:0px;display:none"><?=TranslateHelper::t('采购价') ?></th>
			
			<!-- 
			<th nowrap><?=$sort->link('purchase_by',['label'=>TranslateHelper::t('采购员')]) ?></th>
			<th nowrap><?=$sort->link('prod_weight',['label'=>TranslateHelper::t('重量')]) ?></th>
			<th nowrap><?=$sort->link('update_time',['label'=>TranslateHelper::t('更新日期')]) ?></th>
			-->
	
		</tr>
	        <?php foreach($productData['data'] as $row):?>
	            <tr>
			<td>
				<input name="chk_select_product_product_list" type="checkbox" value="<?= $row['product_id']?>" aria-label="..."">
			</td>
			<td>
				<div style="height: 50px;">
					<img style="max-height: 50px; max-width: 50px;"
						src="<?=$row['photo_primary'] ?>" />
				</div>
			</td>
			<td nowrap><?=$row['sku'] ?>
				<textarea name="select_product_sku" class="hide"><?=$row['sku'] ?></textarea>
				<input type="hidden" name="select_product_product_id" value="<?=$row['product_id'] ?>">
			</td>
			<td><?=$row['name'] ?>
				<textarea name="select_product_name" class="hide"><?=$row['name'] ?></textarea>
			</td>
			<!-- 
			<td><?=(empty($wholeStatusMapping[$row['status']])?'--':$wholeStatusMapping[$row['status']] )?>
				<input type="hidden" name="select_product_status" value="<?=$row['status'] ?>">
			</td>
			 -->
			<td><?=$row['class_name'] ?>
				<input type="hidden" name="select_product_class_name" value="<?=$row['class_name'] ?>">
			</td>
			<td><?=$row['supplier_id'] ?>
				<input type="hidden" name="select_product_supplier_id" value="<?=$row['supplier_id'] ?>">
			</td>
			<td style="display:none"><?=$row['purchase_price'] ?>
				<input type="hidden" name="select_product_purchase_price" value="<?=$row['purchase_price'] ?>">
			</td>
			<!-- 
			<td><?=$row['purchase_by'] ?>
				<input type="hidden" name="select_product_purchase_by" value="<?=$row['purchase_by'] ?>">
			</td>
			<td><?=$row['prod_weight'] ?>
				<input type="hidden" name="select_product_prod_weight" value="<?=$row['prod_weight'] ?>">
			</td>
			<td><?=$row['update_time'] ?>
				<input type="hidden" name="select_product_update_time" value="<?=$row['update_time'] ?>">
			</td>
			-->
		</tr>
	         
	        <?php endforeach;?>
	    </table>
	<!-- table -->
	</div>
	<?php if($productData['pagination']):?>
	<div>
		<div id="select-product-pager" style="clear:both;float:left;width:100%;">
		    <?= \eagle\widgets\SizePager::widget(['isAjax'=>true , 'pagination'=>$productData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
		    <div class="btn-group" style="width: 49.6%;text-align: right;">
		    	<?=\eagle\widgets\ELinkPager::widget(['isAjax'=>true , 'pagination' => $productData['pagination'],'options'=>['class'=>'pagination']]);?>
			</div>
		</div>
	</div>
	<?php endif;?>
</div>
<?php 
// 初始化js配置
$options = array();
$options['pagerId'] = 'select-product-pager';// 包裹 分页widget的id
$options['action'] = \Yii::$app->request->getPathInfo(); // ajax请求的 action
// 当前页码，为保证当前页码与打开的页面一致，
// 这里页码通过后台初始化的Pagination对象的getPage() 获得。
$options['page'] = $productData['pagination']->getPage();
// 当前page size ，为保证当前页码与打开的页面一致， 
// 这里页码通过后台初始化的Pagination对象的getPageSize() 获得。
$options['per-page'] = $productData['pagination']->getPageSize();

$options['sort'] = isset($_REQUEST['sort'])?$_REQUEST['sort']:'';// 当前排序
$this->registerJs('$("#select-product-table").initGetPageEvent('.json_encode($options).')' , \Yii\web\View::POS_READY);

?>