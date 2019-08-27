<?php 
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/app/appView.js", ['depends' => ['yii\web\JqueryAsset']]);
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
	<div class="app-action">
	  <button id="view-app-install-btn" type="button"  class="btn btn-info" appkey="<?=$appInfo["key"] ?>" appname="<?=TranslateHelper::t($appInfo["name"]) ?>"  style="margin-top:10px;<?php if ($appInfo["installed"]=='Y') echo 'display:none;' ?>"><?=TranslateHelper::t('安装app') ?></button>
      <button id="view-app-installed-btn" class="btn btn-default" type="button" style="margin-top:10px;<?php if ($appInfo["installed"]=='N') echo 'display:none;' ?>"><?=TranslateHelper::t('app已安装') ?></button>
	</div>
</div>

<ul class="nav nav-tabs" role="tablist" id="appTab">
  <li role="presentation" class="active"><a href="#basicinfo" aria-controls="basicinfo" role="tab" data-toggle="tab"><?=TranslateHelper::t('应用概况') ?></a></li>
  <li role="presentation"><a href="#usage" aria-controls="usage" role="tab" data-toggle="tab"><?=TranslateHelper::t('使用教程') ?></a></li>
  <li role="presentation"><a href="#example" aria-controls="example" role="tab" data-toggle="tab"><?=TranslateHelper::t('应用截图') ?></a></li>
  
</ul>

<div class="tab-content">
  <div role="tabpanel" class="tab-pane active" id="basicinfo">
      <div class="tabpanel-container" style="padding:15px 15px 10px 15px">
            <p> ebay刊登助手，支持定时刊登。</p>
            <p> 已经为众多卖家提供了超过3年的服务。</p>
            <p>免费版功能包括：商品管理、eBay刊登、eBay订单管理、客服管理……</p>
     </div>
  </div>
  <div role="tabpanel" class="tab-pane" id="usage">...</div>
  <div role="tabpanel" class="tab-pane" id="example">
      <div class="tabpanel-container" style="padding:15px 15px 10px 15px">  
        <img src="/images/app/ebay_listing_example1.jpg"/>
      </div>
  </div>  
  
</div>


