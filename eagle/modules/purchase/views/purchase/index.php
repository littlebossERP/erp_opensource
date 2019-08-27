<?php
use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use yii\jui\Dialog;
use yii\data\Sort;
use eagle\modules\util\helpers\TranslateHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile ( $baseUrl . "js/jquery.json-2.4.js", [ 
		'depends' => [ 
				'yii\jui\JuiAsset',
				'yii\bootstrap\BootstrapPluginAsset' 
		] 
] );
$this->registerCssFile ( $baseUrl . "css/purchase/purchase.css?v=1.1" );

//$this->title = TranslateHelper::t ( '采购管理' );
//$this->params ['breadcrumbs'] [] = $this->title;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<style>
</style>
<div class="flex-row">
	<!-- 左侧列表内容区域 -->
	<?= $this->render('/purchase/_menu') ?>
	<!-- 右侧内容区域 -->
	<div class="content-wrapper" >
		<?=$this->render ( 'list', [
				'list' => $list,
				'pagination' => $pagination,
				'sort' => $sort,
				'warehouse' => $warehouse,
				'suppliers' => $suppliers,
				'purchaseStatus' => $purchaseStatus,
				'paymentStatus' => $paymentStatus,
				'search_condition' => $search_condition ] )?>
	</div> 
	
	<div style="font: 0px/0px sans-serif;clear: both;display: block"> </div> 
</div>



