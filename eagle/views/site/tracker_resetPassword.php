<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile ( \Yii::getAlias ( '@web' ) . "/js/project/siteResetPassword.js" );
	
?>

<?php // 重定向到首页?>
<?php if(isset($initError)):?>
<?php $errorResult = json_decode($initError,true)?>
<script type="text/javascript">
alert('<?= $errorResult['message'] ?>');
</script>
<?php endif;?>

<style>
#footer{
	bottom: 0;
  	position: absolute;
	width: 100%;
	z-index: 1;
}
/* for baidu 统计图片 */
body>a{
	bottom: 0;
  	position: absolute;
}

.reset-password-form-div{
	width:400px;
	margin: auto;
}

#reset-password-form>.form-group {
	display: block;
}

#reset-password-form>.form-group>label {
	width: 46%;
	text-align: right;
	display: inline-block;
}
#reset-password-form>.form-group>input {
	display: inline-block;
}
#reset-password-form>.form-group>label>i {
	color: red;
	padding-right: 5px;
}

.form-group {
	padding: 5px 0;
}

h4{
	font-size: 16px;
}

</style>

<div class="site-reset-password">
	<div class="reset-password-form-div" onkeydown="if(event.keyCode==13){site.resetPassword.save();}">
		<h4>重设置密码</h4>
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
				<label></label>
            	<?= Html::button(TranslateHelper::t('确认'), ['class' => 'btn btn-primary' , 'type' => 'button' , 'onclick' => 'guanwang.index.resetPassword()' ])?>
        	</div>
		</form>
	</div>
</div>