<?php

use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile($baseUrl."css/catalog/catalog.css");

//$this->title = TranslateHelper::t('商品管理');
//$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<style>

</style>

<div class="flex-row">
	<!-- 左侧列表内容区域 -->
	<?= $this->render('/product/_menu', ['class_html' => $class_html, 'classCount' => $classCount]) ?>
	<!-- 右侧table内容区域 -->
	<div class="content-wrapper">
	<?= $this->render('list',
			['productData' => $productData ,
    			'statusMapping'=>$statusMapping ,
    			'brandData'=>$brandData,
    			'tagData'=>$tagData,
    			'supplierData'=>$supplierData,
    			'typeMapping'=>$typeMapping,
    			'prodFieldata'=>$prodFieldata,
	            'search_condition'=>$search_condition,
	            'classData' => $classData,
				'is_catalog_edit' => $is_catalog_edit,
				'is_catalog_export' => $is_catalog_export,
				'is_catalog_delete' => $is_catalog_delete,
			]) ?>
	</div> 
	<div style="font: 0px/0px sans-serif;clear: both;display: block"> </div> 

</div>

		