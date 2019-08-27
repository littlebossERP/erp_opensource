<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;

// $menu_parcel_classification = (!empty($_GET['parcel_classification'])?strtolower($_GET['parcel_classification']):"");
?>

<div class="report-menu">
<ul class="nav nav-pills nav-stacked">
	<li role="presentation">
		<a><?= TranslateHelper::t('库存统计')?></a>
		<ul class="nav nav-pills nav-stacked">
			<li <?=((yii::$app->controller->action->id == 'tag') || (yii::$app->controller->action->id == 'index'))?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/report/inventory/tag'])?>"><?= TranslateHelper::t('标签统计')?></a></li>
			<li <?=(yii::$app->controller->action->id == 'tags')?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/report/inventory/tags'])?>"><?= TranslateHelper::t('多标签统计')?></a></li>
			<li <?=(yii::$app->controller->action->id == 'get-brands-data')?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/report/inventory/get-brands-data'])?>"><?= TranslateHelper::t('品牌统计')?></a></li>
			<li <?=(yii::$app->controller->action->id == 'worth')?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/report/inventory/worth'])?>"><?= TranslateHelper::t('商品数量及统计')?></a></li>
		</ul>
	</li>
	<li role="presentation"  >
		<a><?= TranslateHelper::t('销售商品统计')?></a>
		<ul  class="nav nav-pills nav-stacked">
			<li <?=(yii::$app->controller->action->id == 'product-tag')?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/report/product/product-tag'])?>"><?= TranslateHelper::t('标签统计')?></a></li>
			<li <?=(yii::$app->controller->action->id == 'product-tags')?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/report/product/product-tags'])?>"><?= TranslateHelper::t('多标签统计')?></a></li>
			<li <?=(yii::$app->controller->action->id == 'product-brand')?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/report/product/product-brand'])?>"><?= TranslateHelper::t('品牌统计')?></a></li>
			<li <?=(yii::$app->controller->action->id == 'product-worth')?' class="active"':''?>><a  class="menu_lev2" href="<?= Url::to(['/report/product/product-worth'])?>"><?= TranslateHelper::t('商品数量及统计')?></a></li>
		</ul>
	</li>
</ul>
</div>