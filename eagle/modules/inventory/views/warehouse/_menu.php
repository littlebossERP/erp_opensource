<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;

//$menu_parcel_classification = (!empty($_GET['parcel_classification'])?strtolower($_GET['parcel_classification']):"");
?>
<!-- 左侧标签快捷区域 -->
<div id="sidebar">
	<a id="sidebar-controller" onclick="toggleSidebar();" title="展开收起左侧菜单">&lsaquo;</a>
	<div class="sidebarLv1Title">
		<div>
			<span></span>
			<?= TranslateHelper::t('库存查询')?>
		</div>
	</div>
	<ul class="ul-sidebar-one">
		<li class="ul-sidebar-li<?=((yii::$app->controller->id == 'inventory') and ((yii::$app->controller->action->id == 'index') or (yii::$app->controller->action->id == 'inventory')))?' active':''?>">
			<a href="<?= Url::to(['/inventory/inventory/index'])?>">
				<span class="glyphicon glyphicon-list"></span>
				<span><?= TranslateHelper::t('库存列表')?></span>
			</a>
		</li>
	</ul>
	
	<div class="sidebarLv1Title">
		<div>
			<span></span>
			<?= TranslateHelper::t('出入库操作')?>
		</div>
	</div>
	<ul class="ul-sidebar-one">
		<li class="ul-sidebar-li<?=((yii::$app->controller->id == 'inventory') and (yii::$app->controller->action->id == 'stockchange') )?' active':''?>">
			<a href="<?= Url::to(['/inventory/inventory/stockchange'])?>">
				<span class="glyphicon glyphicon-list-alt"></span>
				<span><?= TranslateHelper::t('出入库记录')?></span>
			</a>
		</li>
		<li class="ul-sidebar-li<?=((yii::$app->controller->id == 'inventory') and (yii::$app->controller->action->id == 'stock_in') )?' active':''?>">
			<a href="<?= Url::to(['/inventory/inventory/stock_in'])?>">
				<span class="glyphicon glyphicon-log-in"></span>
				<span><?= TranslateHelper::t('新建入库')?></span>
			</a>
		</li>
		<li class="ul-sidebar-li<?=((yii::$app->controller->id == 'inventory') and (yii::$app->controller->action->id == 'stock_out') )?' active':''?>">
			<a href="<?= Url::to(['/inventory/inventory/stock_out'])?>">
				<span class="glyphicon glyphicon-log-out"></span>
				<span><?= TranslateHelper::t('新建出库')?></span>
			</a>
		</li>
	</ul>
	
	<div class="sidebarLv1Title">
		<div>
			<span></span>
			<?= TranslateHelper::t('库存盘点')?>
		</div>
	</div>
	<ul class="ul-sidebar-one">
		<li class="ul-sidebar-li<?=((yii::$app->controller->id == 'inventory') and (yii::$app->controller->action->id == 'stocktake') )?' active':''?>">
			<a href="<?= Url::to(['/inventory/inventory/stocktake'])?>">
				<span class="glyphicon glyphicon-tasks"></span>
				<span><?= TranslateHelper::t('库存盘点')?></span>
			</a>
		</li>
	</ul>
	
	<div class="sidebarLv1Title">
		<div>
			<span></span>
			<?= TranslateHelper::t('仓库设置')?>
		</div>
	</div>
	<ul class="ul-sidebar-one">
		<li class="ul-sidebar-li<?=((yii::$app->controller->id == 'warehouse') and (yii::$app->controller->action->id == 'warehouse_list') )?' active':''?>">
			<a href="<?= Url::to(['/inventory/warehouse/warehouse_list'])?>">
				<span class="glyphicon glyphicon-globe"></span>
				<span><?= TranslateHelper::t('仓库列表')?></span>
			</a>
		</li>
	</ul>
</div>