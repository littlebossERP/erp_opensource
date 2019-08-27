<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/tracking/delivery_statistical_analysis.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
//$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);

$this->registerCssFile($baseUrl."css/tracking/tracking.css");

$this->title = TranslateHelper::t('Tracker 物流查询助手 ');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tracking-index col2-layout">
	
	<?= $this->render('_menu') ?>
	<!-- 右侧table内容区域 -->
	<div class="content-wrapper" >
	<?= $this->render('_delivery_statistical_analysis_content',[
    				'analysisData' => $analysisData,
					'addi_params'=>$addi_params,
    				]) ?>
	</div> 
</div>
