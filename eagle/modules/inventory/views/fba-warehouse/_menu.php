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
		    <span class="glyphicon glyphicon-list"></span>
			<?= TranslateHelper::t('Amazon账号/站点')?>
		</div>
	</div>
	
	<?php foreach($accountMaketplaceMap as $Store) {
	?>
	<div class="sidebarLv1Title">
		<div>
			<span></span>
			<?= TranslateHelper::t($Store['merchant']['store_name'])?>
		</div>
	</div>
	<ul class="ul-sidebar-one">
		<?php foreach($Store['marketplace'] as $site) {?>
		<li class="ul-sidebar-li  <?=($Store['merchant']['merchant_id'] == $merchant_id && $site['marketplace_id'] == $marketplace_id) ? 'active' : '' ?>">
			<a href="javascript: Fbawarehouse.list.selectSite('<?=$Store['merchant']['merchant_id']?>','<?=$site['marketplace_id']?>');">
				<span class="glyphicon glyphicon-list"></span>
				<span><?= TranslateHelper::t($site['cn_name'])?></span>
			</a>
		</li>
		<?php }?>
	</ul>
	<?php }?>
</div>