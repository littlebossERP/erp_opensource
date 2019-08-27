<?php 
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/permission/userEdit.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("permission.userEdit.init();" , \yii\web\View::POS_READY);

$colspan = 4;
?>

<div class="permission-user-edit-div" onkeydown="if(event.keyCode==13){permission.userEdit.parentSave();}" style="max-height:700px;overflow:auto;">
	<form id='permission-user-edit' action='<?php echo \Yii::getAlias('@web');?>/permission/user/save' method='post'>
		<input type="hidden" name="_csrf" value="<?= \Yii::$app->request->getCsrfToken() ?>" />
		<input type="hidden" name="user_id" value="<?php echo $user['uid']; ?>" />
	    <input type='hidden' name='user_edit_label' value=1 />
		
		<p style='background-color:silver;font-size: 16px'><b><?= TranslateHelper::t('基本信息') ?></b></p>
		<table class="table">
			<tr><td colspan="<?=$colspan ?>"><?= TranslateHelper::t('Email') ?>:<input class="form-control" type='text' name='email' readonly value='<?php echo $user->email; ?>' /></td></tr>
			<tr><td colspan="<?=$colspan ?>"><?= TranslateHelper::t('用户名') ?>:<input class="form-control" type='text' name='familyname' value='<?= !empty($user->info)?$user->info->familyname:""; ?>' /></td></tr>
			<tr><td colspan="<?=$colspan ?>"><?= TranslateHelper::t('QQ') ?>:<input class="form-control" type='text' name='qq' value='<?= !empty($user->info)?$user->info->qq:""; ?>' /></td></tr>
			<tr><td colspan="<?=$colspan ?>"><?= TranslateHelper::t('登录密码') ?>:<input class="form-control" type='password' name='password' /></td></tr>
		
			<?= $this->render("_permission",['colspan'=>$colspan,'platformList'=>$platformList,'platformAccountList'=>$platformAccountList,'modules'=>$modules,'others'=>$others,"permission"=>$permission,'setting_modules'=>$setting_modules]);?>
		</table>
	
		<div style="clear: both;"></div>
	</form>
</div>
