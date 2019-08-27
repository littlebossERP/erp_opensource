<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/siteResetPassword.js", [ 'depends' => [ 'yii\web\JqueryAsset' ] ] );

if(isset($initError))
	$this->registerJs ( "site.resetPassword.setting.initError='".$initError."';", \yii\web\View::POS_READY );

$this->registerJs ( "site.resetPassword.initWidget();", \yii\web\View::POS_READY );

$this->title = TranslateHelper::t('找回密码');
?>
<style>
#reset-password-form>.form-group {
	display: block;
}

#reset-password-form>.form-group>label {
	width: 150px;
	text-align: right;
}

#reset-password-form>.form-group>label>i {
	color: red;
	padding-right: 5px;
}

.form-group {
	padding: 5px 0;
}
</style>
<div class="site-reset-password">

	<div class="reset-password-form-div" onkeydown="if(event.keyCode==13){site.resetPassword.save();}">
		<form id="reset-password-form" class="form-inline" 
			action="<?php echo  \Yii::getAlias ( '@web' )."/site/reset-password?token=".$token;?>" method="post">
			<input type="hidden" name="_csrf" value="<?= \Yii::$app->request->getCsrfToken() ?>" />
			<div class="form-group">
				<label for="password"> <i>*</i> <?= TranslateHelper::t('密码')?>： </label>
				<input class="form-control" type="password" name="password" id="password" />
			</div>
			<div class="form-group">
				<label for="repassword"> <i>*</i> <?= TranslateHelper::t('确认密码')?>： </label>
				<input class="form-control" type="password" name="repassword" id="repassword" />
			</div>

			<div class="form-group">
				<label><?= Html::resetButton(TranslateHelper::t('重置'), ['class' => 'btn btn-info' ,  'onclick' => 'site.resetPassword.reset()' ])?></label>
            	<?= Html::button(TranslateHelper::t('保存'), ['class' => 'btn btn-primary' , 'type' => 'button' , 'onclick' => 'site.resetPassword.save()' ])?>
        	</div>
		</form>
	</div>
</div>
