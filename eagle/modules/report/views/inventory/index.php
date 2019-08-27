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
// $this->registerJsFile($baseUrl."js/project/inventory/inventory_list.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerJsFile($baseUrl."js/jquery.json-2.4.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/report/reportPublic.css");

$this->title = TranslateHelper::t('报表统计');
// $this->params['breadcrumbs'][] = $this->title;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"> 
<style>
</style>
<div class="report-index">
    <h1><?php echo Html::encode($this->title);?></h1>

	<ul class="list-unstyled list-inline">
		<li class="content_left"><?= $this->render('_menu') ?></li>
		<li class="content_right"><?= $this->render('tag',
				['allTagData' => $allTagData ]) ?></li>
	</ul>
</div>