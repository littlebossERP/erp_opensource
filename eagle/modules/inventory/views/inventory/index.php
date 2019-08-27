<?php

use yii\helpers\Html;
use yii\grid\GridView;
use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
//$this->registerJsFile($baseUrl."js/project/tracking/manual_import.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/inventory/inventory_list.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/project/inventory/downloadexcel.js?v=1.0", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/inventory/inventory.css");
//$this->registerJsFile($baseUrl."js/project/tracking/ajaxfileupload.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

//$this->title = TranslateHelper::t('仓储管理');
//$this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<style>

</style>
<div class="flex-row">
	<!-- 左侧列表内容区域 -->
	<?= $this->render('/inventory/_menu', ['class_html' => '']) ?>
	<!-- 右侧内容区域 -->
	<div class="content-wrapper" >
	<?= $this->render('_stockagelist',
				['stockData' => $stockData ,
				 'sort'=>$sort,
				 'warehouse'=>$warehouse,
				 'prodStatus'=>$prodStatus,
				 'prodTypes'=>$prodTypes,
				 'search_condition'=>$search_condition,
				]) ?>
	</div> 
	<div style="font: 0px/0px sans-serif;clear: both;display: block"> </div> 
</div>

