<?php

use yii\helpers\Html;
use yii\grid\GridView;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/catalog/tag.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
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
.tag_info .modal-dialog {
	max-width:400px;
	max-height: 500px;
	overflow: auto;
}

</style>
<div class="flex-row">
	<!-- 左侧列表内容区域 -->
	<?= $this->render('/product/_menu', ['class_html' => '']) ?>
	<!-- 右侧内容区域 -->
	<div class="content-wrapper" >
		<form  class="form-horizontal" action="<?= Url::to(['/catalog/tag/'.yii::$app->controller->action->id])?>" method="get" style="width:100%;float:left;">
			<div class="div-input-group" style="width: 25%;float:right;margin:5px;">
				<div class="input-group" style="float:right;">
					<input name='keyword'  class="form-control" style="width: 75%;float:right;height: 28px;" placeholder="<?= TranslateHelper::t('输入标签名称字段')?>"
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
				<button type="button" class="btn-xs btn-transparent font-color-1" onclick="tag.list.addTag()" style="margin:0px 5px;font-size: 12px;">
					<span class="glyphicon glyphicon-plus"></span>
					<?= TranslateHelper::t('添加标签')?>
				</button>
			</div>
			<div style="float:left;">
				<button type="button" class="btn-xs btn-transparent font-color-1" onclick="tag.list.batchDeleteTag()" style="margin:0px 5px;font-size: 12px;">
					<span class="egicon-trash" aria-hidden="true" style="height:16px;"></span>
					<?= TranslateHelper::t('批量删除')?>
				</button>
			</div>
		</form>
		<div style="width:100%;float:left;">
			<table id="tag_list_tb" style="width: 100%;float:left;font-size: 12px;" class="table table-hover">
				<tr>
					<th style="width: 30px;">
						<input type="checkbox" name="chk_tag_all">
					</th>
					<th style="width: 100px;"><?=TranslateHelper::t('tag_id')?></th>
					<th style="width: 500px;"><?=TranslateHelper::t('标签名称')?></th>
					<th style="width: 100px;"><?=TranslateHelper::t('操作')?></th>
				</tr>
	<?php if(count($tagData['data'])>0){ 
			foreach ($tagData['data'] as $index=>$row){ ?>
				<tr <?=!is_int($index / 2)?"class='striped-row'":"" ?>>
					<td>
						<input type="checkbox" name="chk_tag_info" value="<?=$row['tag_id']?>">
					</td>
					<td><?=$row['tag_id']?></td>
					<td><?=$row['tag_name']?></td>
					<td>
						<button type="button" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('修改')?>" onclick="tag.list.editTag(<?=$row['tag_id']?>,'<?=htmlentities($row['tag_name'])?>')">
							<span class="glyphicon glyphicon-edit" aria-hidden="true" style="top:2px;"></span>
						</button>
						<button type="button" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('查看产品')?>" onclick="tag.list.viewProdInTag(<?=$row['tag_id']?>)">
							<span class="glyphicon glyphicon-list-alt" aria-hidden="true" style="top:2px;"></span>
						</button>
						<button type="button" class="btn-xs btn-transparent font-color-1" title="<?= TranslateHelper::t('删除')?>" onclick="tag.list.deleteTag(<?=$row['tag_id']?>,this)">
							<span class="egicon-trash" aria-hidden="true" style="height:16px;"></span>
						</button>
					</td>
				</tr>
			
		<?php } ?>
	<?php }else{ ?>
				<tr><td colspan="4"><b><?=TranslateHelper::t('没有任何标签')?></b></td></tr>
	<?php } ?>
			</table>
		</div>
	<!-- pagination -->
	<?php if($tagData['pagination']):?>
	<div>
	    <?= \eagle\widgets\SizePager::widget(['pagination'=>$tagData['pagination'] , 'pageSizeOptions'=>array( 5 , 20 , 50 , 100 , 200 ) , 'class'=>'btn-group dropup']);?>
	    <div class="btn-group" style="width: 49.6%;text-align: right;">
	    	<?=\yii\widgets\LinkPager::widget(['pagination' => $tagData['pagination'],'options'=>['class'=>'pagination']]);?>
		</div>
	</div>
	<?php endif;?>
	<!-- /.pagination-->

		<div class="tag_info"></div>
		<div class="product_in_tag"></div>
	</div>
</div>