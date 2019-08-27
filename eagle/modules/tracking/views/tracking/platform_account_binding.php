<?php
use yii\helpers\Html;
use yii\helpers\Url;
use eagle\modules\util\helpers\TranslateHelper;

$this->title = TranslateHelper::t('Tracker 物流查询助手 ');
$this->params['breadcrumbs'][] = $this->title;

$title_lv2 =  TranslateHelper::t('平台绑定 ');
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerCssFile($baseUrl."css/tracking/tracking.css");
?>
<div class="tracking-index">
	<ul class="list-unstyled list-inline">

		<li class="content_left"><?= $this->render('_menu') ?></li>
		<li class="content_right">

			<div class="panel panel-default">
				
				<div class="panel-body">
					<br>
					<p><?= TranslateHelper::t('请移转到小老板为tracker物流查询助手绑定平台账号:');?></p>
					<br>
					<p><?= TranslateHelper::t('1.移动鼠标到 基础设置->平台账号设置, 选择需要绑定的账号,如下图所示');?></p>
					<br>
					<img alt="" src="<?php echo Yii::getAlias('@web');?>/images/tracking_binding_step1.png"
						style="width: 100%;"> 
						<br>
						<br>
					<p><?= TranslateHelper::t('2.如进入ebay平台绑定页面, 点击增加, 如下图所示: ')?></p>
					<br>
					<img alt="" src="<?php echo Yii::getAlias('@web');?>/images/tracking_binding_step2.png"
						style="width: 100%;">
				</div>

			</div>

		</li>

	</ul>

</div>
