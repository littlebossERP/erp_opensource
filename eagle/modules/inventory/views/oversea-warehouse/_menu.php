<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;

//$menu_parcel_classification = (!empty($_GET['parcel_classification'])?strtolower($_GET['parcel_classification']):"");
$baseUrl = \Yii::$app->urlManager->baseUrl;
?>
<!-- 左侧标签快捷区域 -->
<div id="sidebar" style="width: 250px;">
	<a id="sidebar-controller" onclick="toggleSidebar();" title="展开收起左侧菜单">&lsaquo;</a>
	<div class="sidebarLv1Title">
		<div>
			<?= TranslateHelper::t('已授权海外仓')?>
		</div>
	</div>
	<ul class="ul-sidebar-one">
	    <?php foreach($OverseaWarehouse as $ow) {?>
		<li class="ul-sidebar-li  <?=$ow['warehouse_id'] == $warehouse_id ? 'active' : '' ?>">
			<a href="javascript: OverseaWarehouse.list.selectStock('<?= $ow['warehouse_id']?>','<?= $ow['carrier_code']?>','<?= $ow['third_party_code']?>');">
				<span class="glyphicon glyphicon-list"></span>
				<span><?= TranslateHelper::t($ow['carrier_name'])?></span>
			</a>
		</li>
		<?php }?>
	</ul>
	<div class="sidebarLv1Title">
		<div style="color: #999;">
			<?= TranslateHelper::t('未授权海外仓')?>
		</div>
	</div>
	<ul class="ul-sidebar-one">
	    <?php foreach($carrier_not_use as $car) {?>
		<li class="ul-sidebar-li ">
			<a target="_blank" href="/configuration/carrierconfig/index?tab_active=oversea&search_carrier_code_post=<?=$car['carrier_code']?>">
				<span class="glyphicon glyphicon-list" style="color: #999;"></span>
				<span style="color: #999;"><?= TranslateHelper::t($car['carrier_name'])?></span>
			</a>
		</li>
		<?php }?>
	</ul>
</div>