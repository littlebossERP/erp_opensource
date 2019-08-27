<?php
use eagle\modules\util\helpers\TranslateHelper;

$this->title = TranslateHelper::t('Tracker 物流查询助手 ');
$this->params['breadcrumbs'][] = $this->title;

$title_lv2 =  TranslateHelper::t('平台绑定 ');
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile($baseUrl."css/tracking/tracking.css");

if( 'aliexpress' == $platform){
	$this->registerJsFile($baseUrl."js/project/platform/aliexpressAccountList.js");
}

if( 'ebay' == $platform){
	$this->registerJsFile($baseUrl."js/project/platform/ebayAccountsList.js");
}




?>
<style>
.table td,.table th{
	text-align: center;
}

</style>
<div class="tracking-index">
	<ul class="list-unstyled list-inline">

		<li class="content_left"><?= $this->render('_menu') ?></li>
		<li class="content_right">

			<div class="panel panel-default">
				<div class="panel-body">
					<DIV style="text-align:center;">
						<?php if( 'aliexpress' == $platform):?>
						<p><?= TranslateHelper::t('哎呀，没找到您有绑定速卖通账号，绑定账号后再来看看？')?></p>
						<p><?= TranslateHelper::t(' (首次绑定需约1小时的同步初始时间)') ?></p>
						
						<a style="text-decoration: none;" href="javascript:void(0)" onclick="authorizationUser()"><?= TranslateHelper::t('添加绑定') ?></a>
							
						<?php elseif( 'ebay' == $platform):?>
						<p><?= TranslateHelper::t('哎呀，没找到您有绑定ebay账号，绑定账号后再来看看？')?></p>
						<a style="text-decoration: none;" href="javascript:void(0)" onclick="platform.ebayAccountsList.menuAdd()"><?= TranslateHelper::t('立即绑定') ?></a>
						
						<?php else :?>
						<?php endif;?>				
					</DIV>
				</div>
			</div>

		</li>

	</ul>

</div>
