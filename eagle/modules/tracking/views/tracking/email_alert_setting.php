<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

//$this->title = TranslateHelper::t('自动邮件提醒设置');
$this->title = TranslateHelper::t('Tracker 物流查询助手 ');
$this->params['breadcrumbs'][] = $this->title;
$baseUrl = \Yii::$app->urlManager->baseUrl . '/';
$this->registerJsFile($baseUrl."js/project/tracking/email_alert_setting.js", ['depends' => ['yii\jui\JuiAsset','yii\bootstrap\BootstrapPluginAsset']]);
$this->registerCssFile($baseUrl."css/tracking/tracking.css");
?>

<style>
.float_left{
	float:left;
}

.content_left{
	width:20%;
	vertical-align: top;
	
}

.content_right{
	width:78%;
	vertical-align: top;
	
}

.td_space_toggle{
	height: auto;
	padding: 0!important;
}

.div_space_toggle{
	display:none;
}

.menu_lev2{
	padding-left: 40px!important;
}

.date_input{
	width: 20px;
}

form ul li{
	margin-bottom: 3px;
}

</style>
<div class="tracking-index">
<ul class="list-unstyled list-inline">
	
	<li class="content_left"><?= $this->render('_menu') ?></li> 
	<li class="content_right"><?= $this->render('_email_alert_setting_content',['config'=>$config]) ?></li> 
	
</ul>

</div>


