<?php
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/siteUserGuide2.js");
$this->registerJs ( "site.userGuide2.init();", \yii\web\View::POS_READY );
$imageBasePath=Yii::getAlias('@web')."/images";
?>

<style>
@media (min-width: 768px) {
  .user-guide-view-app-modal .modal-dialog {
    width: 600px;
  }
}

@media (min-width: 992px) {
	.user-guide-view-app-modal .modal-dialog {
		width: 750px;
	}
}

@media (min-width: 1200px) {
	.user-guide-view-app-modal .modal-dialog {
		width: 970px;
	}
}
/* modal限高设置 */
.user-guide-view-app-modal .modal-body {
	max-height: 430px !important;
	overflow-y: auto !important;
}

#user-guide-chosen-app-list ul{
	padding: 0;
}
#user-guide-chosen-app-list li{
	list-style: none;
}
.row> li:first-child{
	padding: 0;
	text-align: right;
} 
#user-guide-chosen-app-list .app-description {
	height: 20;
	text-overflow: ellipsis;
	overflow: hidden;
	white-space: nowrap;	
}
</style>
<p><?= TranslateHelper::t('根据您的需求，小老板给你推荐以下应用：') ?></p>
 
 
 <!-- 所有app的列表  -->
<div id="user-guide-chosen-app-list">
	<form id="user-guide-2-form" >
		<ul class="" style="display: block;clear:both">
		<?php 
		$rowIndex = 1;
		$lastRowIndex = count($allAppList);
		foreach($allAppList as $appInfo):  ?>
			<li class="col-sm-6 col-xs-12" appkey="<?=$appInfo["key"] ?>" appname="<?=TranslateHelper::t($appInfo["name"]) ?>" >
				<ul class="row">
					<li class="checkbox form-group col-sm-2 col-xs-4">
						<label><input type="checkbox" name="<?=$appInfo["key"] ?>" value="1">添加</label>
					</li>
					
					<li class="col-sm-3 col-xs-6"> 
						<a href="#" target="_self" appkey="<?=$appInfo["key"] ?>" appname="<?=TranslateHelper::t($appInfo["name"]) ?>" >
							<img style="width: 83px; height: 81px;" src="<?php echo $imageBasePath."/app/".$appInfo["key"].".jpg"; ?>">
						</a>
					</li>
					<li class="col-sm-7 col-xs-12">
						<h4 class="hide">
							<a href="#" target="_self" appkey="<?=$appInfo["key"] ?>" appname="<?=TranslateHelper::t($appInfo["name"]) ?>" ><?=TranslateHelper::t($appInfo["name"]) ?></a>
						</h4>
						<p class="" title="<?=$appInfo["description"] ?>"><?=$appInfo["description"] ?></p>
						<div class="btn-group" role="group">
							<button class="btn btn-default btn-xs disabled" type="button" style="margin-top:10px"><?=TranslateHelper::t('免费') ?></button>
							<?php if ($appInfo["installed"]=='N' or $appInfo["is_active"]=='N' ) { ?>
							     <button class="btn btn-default btn-xs disabled" type="button" style="margin-top:10px"><?=TranslateHelper::t('未启用') ?></button>
							     <button class="btn btn-info btn-xs app-detail-btn" type="button" style="margin-top:10px" onclick="site.userGuide2.appDetailView('<?=$appInfo["key"] ?>' , '<?=TranslateHelper::t($appInfo["name"]) ?>');" ><?=TranslateHelper::t('详情') ?></button>
							<?php } else { ?>
							     <button class="btn btn-default btn-xs disabled" type="button" style="margin-top:10px"><?=TranslateHelper::t('已启用') ?></button>
							     <button class="btn btn-info btn-xs app-detail-btn" type="button" style="margin-top:10px" onclick="site.userGuide2.appDetailView('<?=$appInfo["key"] ?>' , '<?=TranslateHelper::t($appInfo["name"]) ?>');" ><?=TranslateHelper::t('详情') ?></button>
							<?php } ?>
						</div>
					</li>
				</ul>
			</li>
			<?php if($rowIndex % 2 == 0 && $rowIndex != $lastRowIndex):?>
			<li class="clearfix hidden-xs" style="padding-bottom: 20px;"></li>
			<?php endif;?>
			<?php $rowIndex++;?>
		<?php endforeach; ?>
		</ul>
	</form>
</div>