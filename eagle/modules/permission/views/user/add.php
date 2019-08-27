<?php 
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/permission/userAdd.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("permission.userAdd.init();" , \yii\web\View::POS_READY);

$colspan = 4;
?>

<div class="permission-user-add-div" onkeydown="if(event.keyCode==13){permission.userAdd.save();}" style="max-height:700px;overflow:auto;">
	<form id='permission-user-add' action='<?php echo \Yii::getAlias('@web');?>/permission/user/save' method='post'>
		<input type="hidden" name="_csrf" value="<?= \Yii::$app->request->getCsrfToken() ?>" />
		<input type='hidden' name='user_add_label' value=1 />
		<p style='background-color:silver;'><b><?= TranslateHelper::t('基本信息') ?></b></p>
		<table class="table">
			<tr><td colspan="<?=$colspan ?>"><span style='color:red'>*</span><?= TranslateHelper::t('Email') ?>:<input class="form-control" type='text' name='email' value='' /> </td></tr>
			<tr><td colspan="<?=$colspan ?>"><?= TranslateHelper::t('用户名') ?>:<input class="form-control" type='text' name='familyname' value='' /></td></tr>
			<tr><td colspan="<?=$colspan ?>"><span style='color:red'>*</span><?= TranslateHelper::t('QQ') ?>:<input class="form-control" type='text' name='qq' /></td></tr>
			<tr><td colspan="<?=$colspan ?>"><span style='color:red'>*</span><?= TranslateHelper::t('登录密码') ?>:<input class="form-control" type='password' name='password' /></td></tr>
		
			<?= $this->render("_permission",['colspan'=>$colspan,'platformList'=>$platformList,'platformAccountList'=>$platformAccountList,'modules'=>$modules,'others'=>$others,'setting_modules'=>$setting_modules]);?>
		</table>
	
	    
	</form>
</div>