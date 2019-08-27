<?php
use eagle\modules\util\helpers\TranslateHelper;

if( isset(\Yii::$app->params["isOnlyForOneApp"]) and \Yii::$app->params["isOnlyForOneApp"]==1 ){
	$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/siteRequestPassReset.js");
	$this->registerJs ( "site.requestPassReset.initWidget();", \yii\web\View::POS_END );
}else{
	$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/siteRequestPassReset.js", [ 'depends' => [ 'yii\web\JqueryAsset' ] ] );
	$this->registerJs ( "site.requestPassReset.initWidget();", \yii\web\View::POS_READY );
}


$this->title = TranslateHelper::t('找回密码');
?>
<?php if( isset(\Yii::$app->params["isOnlyForOneApp"]) and \Yii::$app->params["isOnlyForOneApp"]==1 ):?>
<style>
#site-request-password-reset-form>.form-group {
	display: block;
}

#site-request-password-reset-form>.form-group>label:first-child {
	width: 150px;
	text-align: right;
}

#site-request-password-reset-form>.form-group>label>i {
	color: red;
	padding-right: 5px;
}

.form-group {
	padding: 5px 0;
}
</style>
<div class="modal fade" id="forgetpass-modal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title"><?= TranslateHelper::t('找回密码') ?></h4>
      </div>
      <div class="modal-body">
        <div class="site-request-password-reset">
			<div class="request-password-reset-form" onkeydown="if(event.keyCode==13){site.requestPassReset.sentMail();}">
				<form id="site-request-password-reset-form" action="<?php echo  \Yii::getAlias ( '@web' )."/"?>site/request-password-reset" class="form-inline" method="post">
					<input type="hidden" name="_csrf" value="<?= \Yii::$app->request->getCsrfToken() ?>" />
					<div class="form-group">
						<label for="email"><?= TranslateHelper::t('账号(用户注册邮箱)') ?>：</label>
						<input type="text" name="email" id="email" class="form-control" />
					</div>
				</form>
			</div>
		</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal"><?= TranslateHelper::t('取消')?></button>
        <button type="button" class="btn btn-primary" onclick="site.requestPassReset.sentMail();"><?= TranslateHelper::t('发送') ?></button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

	
<?php else :?>

<style>
#site-request-password-reset-form>.form-group {
	display: block;
}

#site-request-password-reset-form>.form-group>label:first-child {
	width: 150px;
	text-align: right;
}

#site-request-password-reset-form>.form-group>label>i {
	color: red;
	padding-right: 5px;
}

.form-group {
	padding: 5px 0;
}
</style>

<div class="site-request-password-reset">

	<p>Please fill out your email. A link to reset password will be sent
		there.</p>

	<div class="request-password-reset-form" onkeydown="if(event.keyCode==13){site.requestPassReset.sentMail();}">
		<form id="site-request-password-reset-form" action="<?php echo  \Yii::getAlias ( '@web' )."/"?>site/request-password-reset" class="form-inline" method="post">
			<input type="hidden" name="_csrf" value="<?= \Yii::$app->request->getCsrfToken() ?>" />
			<div class="form-group">
				<label for="email">账号(用户注册邮箱)：</label>
				<input type="text" name="email" id="email" class="form-control" />
			</div>
			<div class="form-group">
				<label></label>
				<button class="btn btn-primary" type="button" onclick="site.requestPassReset.sentMail();">发送</button>
			</div>
		</form>
	</div>

	</div>
	
<?php endif;?>

