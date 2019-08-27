<?php

use yii\helpers\Html;
use yii\grid\GridView;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/catalog/brand.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/catalog/catalog.css");

//$this->title = TranslateHelper::t('商品管理');
//$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<style>
.bg_loading {
	background-image: url(/../images/loading.gif);
	background-repeat: no-repeat;
	background-position: center;
}
/*
table > tbody > tr > th{
	height: 20px;
  	padding: 3px;
  	vertical-align: middle;
	text-align: center !important;
	background-color: #d9effc;
	font: bold 12px SimSun,Arial;
	color: #374655;
}
table > tbody > tr > td{
  	vertical-align: middle;
	text-align: center;
	word-break:break-word;
}
*/
.cursor_pointer{
	cursor: pointer;
}
ul li a{
	cursor:pointer;
}
.ui-autocomplete {
z-index: 2000;
}
.popover-content{
	background-color: rgb(255, 168, 168);
}
</style>
<div class="flex-row">
	<!-- 左侧列表内容区域 -->
	<?= $this->render('/product/_menu', ['class_html' => '']) ?>
	<!-- 右侧内容区域 -->
	<div class="content-wrapper" >

		<form  class="form-horizontal" action="<?= Url::to(['/catalog/brand/'.yii::$app->controller->action->id])?>" method="get" style="width:100%;float:left;">
			<div class="div-input-group" style="width: 25%;float:right;margin:5px;">
				<div class="input-group" style="float:right;">
					<input name='keyword'  class="form-control" style="width: 75%;float:right;height: 28px;" placeholder="<?= TranslateHelper::t('输入品牌名称或备注字段')?>"
					value='<?= ( (!isset($_GET['keyword']))  ?'':$_GET['keyword'])?>' />
					<span class="input-group-btn" class="form-control" style="">
						<button type="submit" class="btn btn-default">
							<span class="glyphicon glyphicon-search" aria-hidden="true"></span>
					    </button>
				    </span>
				<?php ?>
				</div>
			</div>
			<div style="float:left;">
				<button type="button" class="btn-xs btn-transparent font-color-1" onclick="brand.list.addBrand()" style="margin: 5px 0px;font-size: 12px;">
					<span class="glyphicon glyphicon-plus"></span>
					<?= TranslateHelper::t('添加品牌')?>
				</button>
			</div>
			<div style="float:left;">
				<button type="button" class="btn-xs btn-transparent font-color-1" onclick="brand.list.batchDeleteBrand()" style="margin: 5px 0px;font-size: 12px;">
					<span class="egicon-trash" aria-hidden="true" style="height:16px;"></span>
					<?= TranslateHelper::t('批量删除')?>
				</button>
			</div>
		</form>
		<div style="width:100%;float:left;">
			<table id="brand_list_tb" style="width:100%;font-size:12px;float:left;" class="table table-hover">
				<tbody>
					<tr>
						<th style="width: 30px;text-align: center;">
							<input type="checkbox" name="chk_brand_all">
						</th>
						<th style="display:none;"><?=TranslateHelper::t('品牌id')?></th>
						<th style="width: 200px;"><?=TranslateHelper::t('品牌名称')?></th>
						<th style="width: 300px;"><?=TranslateHelper::t('备注')?></th>
						<th style="width: 100px;"><?=TranslateHelper::t('修改人')?></th>
						<th style="width: 150px;"><?=TranslateHelper::t('创建时间')?></th>
						<th style="width: 150px;"><?=TranslateHelper::t('最后修改时间')?></th>
						<th style="width: 200px;"><?=TranslateHelper::t('操作')?></th>
					</tr>
		<?php if(count($brandData['data'])>0){ 
				foreach ($brandData['data'] as $index=>$row){ ?>
					<tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
						<td style="text-align: center;">
							<input type="checkbox" name="chk_brand_info" value="<?=$row['brand_id']?>">
						</td>
						<td style="display:none;"><?=$row['brand_id']?></td>
						<td><?=htmlentities($row['name'])?></td>
						<td><?=htmlentities($row['comment'])?></td>
						<td><?=$row['capture_user_name']?></td>
						<td><?=$row['create_time']?></td>
						<td><?=$row['update_time']?></td>
						<td style="text-align: center;">
							<button type="button" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('查看')?>" onclick="brand.list.viewBrand(<?=$row['brand_id']?>)" style="vertical-align:middle;">
								<span class="egicon-eye"></span>
							</button>
							<button type="button" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('编辑')?>" onclick="brand.list.editBrand(<?=$row['brand_id']?>)" style="vertical-align:middle;">
								<span class="glyphicon glyphicon-edit" aria-hidden="true" style="top:2px;"></span>
							</button>
							<button type="button" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('查看产品')?>" onclick="brand.list.viewProdInBrand(<?=$row['brand_id']?>)" style="vertical-align:middle;">
								<span class="glyphicon glyphicon-list-alt" aria-hidden="true" style="top:2px;"></span>
							</button>
							<button type="button" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('删除')?>" onclick="brand.list.deleteBrand(<?=$row['brand_id']?>,this)" style="vertical-align:middle;">
								<span class="egicon-trash" aria-hidden="true" style="height:16px;"></span>
							</button>
						</td>
					</tr>
				
			<?php } ?>
		<?php }else{ ?>
					<tr><td colspan="8"><b><?=TranslateHelper::t('没有任何品牌')?></b></td></tr>
		<?php } ?>
				</tbody>
			</table>
		</div>
		<!-- pagination -->
			<?php if($brandData['pagination']):?>
			<div>
			    <?= \eagle\widgets\SizePager::widget(['pagination'=>$brandData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
			    <div class="btn-group" style="width: 49.6%;text-align: right;">
			    	<?=\yii\widgets\LinkPager::widget(['pagination' => $brandData['pagination'],'options'=>['class'=>'pagination']]);?>
				</div>
			</div>
			<?php endif;?>
		<!-- /.pagination-->
	</div> 
	<div style="font: 0px/0px sans-serif;clear: both;display: block"> </div> 

</div>
<div class="brand_info"></div>
<div class="product_in_brand"></div>