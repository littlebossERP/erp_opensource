<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>
<!-- 左侧标签快捷区域 -->
<div id="sidebar">
	<a id="sidebar-controller" onclick="toggleSidebar();" title="展开收起左侧菜单">&lsaquo;</a>
	<div class="sidebarLv1Title">
		<div>
			<span class=""></span>
			<?= TranslateHelper::t('采购单')?>
		</div>
	</div>
	<ul class="ul-sidebar-one">
		<li class="ul-sidebar-li<?=((yii::$app->controller->id == 'purchase') and (yii::$app->controller->action->id == 'index'))?' active':''?>">
			<a class="" href="<?= Url::to(['/purchase/purchase/index'])?>">
				<span class="glyphicon glyphicon-list"></span>
				<span><?= TranslateHelper::t('采购单列表')?></span>
			</a>
		</li>
	</ul>
	
	<div class="sidebarLv1Title">
		<div>
			<span class=""></span>
			<?= TranslateHelper::t('采购建议')?>
		</div>
	</div>
	<ul class="ul-sidebar-one">
		<li class="ul-sidebar-li<?=((yii::$app->controller->id == 'purchasesug') and (yii::$app->controller->action->id == 'meet-order'))?' active':''?>">
			<a class="" href="<?= Url::to(['/purchase/purchasesug/meet-order'])?>">
				<span class="glyphicon glyphicon-edit"></span>
				<span qtipkey="meet-order-purchase"><?= TranslateHelper::t('见单采购')?></span>
			</a>
		</li>
		<li class="ul-sidebar-li<?=((yii::$app->controller->id == 'purchasesug') and (yii::$app->controller->action->id == 'sugindex'))?' active':''?>">
			<a class="" href="<?= Url::to(['/purchase/purchasesug/sugindex'])?>">
				<span class="glyphicon glyphicon-exclamation-sign"></span>
				<span qtipkey="how-purchasesug-work"><?= TranslateHelper::t('采购建议列表')?></span>
			</a>
		</li>
		<li class="ul-sidebar-li<?=((yii::$app->controller->id == 'purchasesug') and (yii::$app->controller->action->id == 'sug_strategies'))?' active':''?>">
			<a class="" href="<?= Url::to(['/purchase/purchasesug/sug_strategies'])?>">
				<span class="glyphicon glyphicon-cog"></span>
				<span><?= TranslateHelper::t('备货策略')?></span>
			</a>
		</li>
		
	</ul>
	
	<div class="sidebarLv1Title">
		<div>
			<span class=""></span>
			<?= TranslateHelper::t('产品供应商')?>
		</div>
	</div>
	<ul class="ul-sidebar-one">
		<li class="ul-sidebar-li<?=((yii::$app->controller->id == 'supplier') and (yii::$app->controller->action->id == 'index'))?' active':''?>">
			<a class="" href="<?= Url::to(['/purchase/supplier/index'])?>">
				<span class="glyphicon glyphicon-shopping-cart"></span>
				<span><?= TranslateHelper::t('供应商列表')?></span>
			</a>
		</li> 
		<li class="ul-sidebar-li<?=((yii::$app->controller->id == 'supplier') and (yii::$app->controller->action->id == 'pd-suppliers'))?' active':''?>">
			<a class="" href="<?= Url::to(['/purchase/supplier/pd-suppliers'])?>">
				<span class="glyphicon glyphicon glyphicon-yen"></span>
				<span><?= TranslateHelper::t('查看报价')?></span>
			</a>
		</li>
	</ul>
</div>