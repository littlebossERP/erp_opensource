<?php

use yii\helpers\Html;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile($baseUrl."css/catalog/catalog.css");

//$this->title = TranslateHelper::t('商品管理');
//$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<style>
.do{
	color:#ffffff;
	background:rgb(3,206,89) !important;
	border-radius:3px;
}
</style>

<div class="catalog-index col2-layout">
	
	<?= $this->render('_menu') ?>
	<!-- 右侧table内容区域 -->
	<div class="content-wrapper" >
	<!-- table -->
<div style="width:100%;float:left;">
	<table cellspacing="0" cellpadding="0" style="font-size: 12px;width:100%;float:left;"
		class="table table-hover">
		<thead>
		<tr>
			<th nowrap >商品图片</th>
			<th nowrap >商品名称</th>
			<th nowrap >品牌名称</th>
			<th nowrap width="20%">操作</th>
		</tr>
		</thead>
		<tbody>
        <?php if (count($fanbens)):foreach($fanbens as $fanben):?>
        	<tr>
        	<td><img <?php if (strlen($fanben->main_image)){echo 'src="'.$fanben->main_image.'"';}?> width="60px" height="60px"></td>
        	<td><?=$fanben->name?></td>
        	<td><?=$fanben->brand?></td>
        	<td>
        	<?php $platform=['wish'=>'wish','lazada'=>'lazada']?>
        	<?=Html::dropDownList('do','',$platform,['onchange'=>"renlingone($(this).val(),'".$fanben->id."');",'class'=>'do','prompt'=>'认领到','onmousedown'=>'$(this).val("")']);?>
        	</td>
        	</tr>
        <?php endforeach;endif;?>
        </tbody>
    </table>
	<!-- table -->
	</div> 
	<div style="font: 0px/0px sans-serif;clear: both;display: block"> </div> 

</div>
<script>
	function renlingone(platform,id){
		$.post(global.baseUrl+"catalog/product/renlingfanben",{platform:platform,ids:id},function(r){
			$.hideLoading();
			r=eval('('+r+')');
			if(r['success']==true){
				bootbox.alert('操作已成功');
			}else{
				bootbox.alert('操作失败:'+r['message']);
			}
		});
	}
</script>
		