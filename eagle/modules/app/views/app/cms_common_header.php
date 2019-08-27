<?php 
use eagle\modules\util\helpers\TranslateHelper;

$imageBasePath=Yii::getAlias('@web')."/images";

?>

<style>
.view-app-modal .modal-dialog{
	width: 750px;
}

/* modal限高设置 */
.view-app-modal .modal-body {
max-height: 590px;
min-height: 530px;
overflow-y: auto;
}
.app-desc h1 {
font-size: 14px;
color: #444;
}
.app-header {
padding: 40px 50px;
}
.app-logo {
float: left;
}
.app-logo img {
width: 100px;
height: 100px;
border-radius: 10px;
border: 1px solid #ececec;
padding: 1px;
}
.app-desc {
float: left;
margin-left: 20px;
width: 400px;
padding-top: 5px;
}
.app-action {
float: right;
padding: 20px 10px 0 0;
}
.app-desc p {
font-size: 13px;
margin-top: 10px;
line-height: 25px;
color: #777;
}
</style>

<div class="app-header clearfix">
	<div class="app-logo">
		<img src="<?=$imageBasePath."/app/".$appInfo["key"].".jpg" ?>">
	</div>
	<div class="app-desc">
		<h1><?=TranslateHelper::t($appInfo["name"]) ?></h1>
		<p><?=$appInfo["description"] ?></p>
	</div>
	<div class="app-action"  appkey="<?=$appInfo["key"] ?>" appname="<?=TranslateHelper::t($appInfo["name"]) ?>">
	  <button class="btn btn-default disabled" type="button" style="margin-top:10px"><?=TranslateHelper::t('免费') ?></button>
	  <?php 
	   if ($showOperation==1) {
	     if ($appInfo["installed"]=='N' or $appInfo["is_active"]=='N') {
          ?>
          <button id="view-app-install-btn" type="button"  class="btn btn-info"  style="margin-top:10px"><?=TranslateHelper::t('启用app') ?></button>
      <?php  }  else {?>
          <button id="view-app-unactivate-btn" type="button"  class="btn btn-danger"  style="margin-top:10px;"><?=TranslateHelper::t('停用app') ?></button>
      <?php } 
      }
      ?>	
	</div>
</div>

<ul class="nav nav-tabs" role="tablist" id="appTab">
  <li role="presentation" class="active"><a href="#basicinfo" aria-controls="basicinfo" role="tab" data-toggle="tab"><?=TranslateHelper::t('应用概况') ?></a></li>
  <li role="presentation"><a href="#usage" aria-controls="usage" role="tab" data-toggle="tab"><?=TranslateHelper::t('使用教程') ?></a></li>
  <li role="presentation"><a href="#example" aria-controls="example" role="tab" data-toggle="tab"><?=TranslateHelper::t('应用截图') ?></a></li>
</ul>

