<?php 
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/permission/userEdit.js?v=1.2", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("permission.userEdit.init();" , \yii\web\View::POS_READY);

$this->title = "编辑账号";
?>
<div class="permission-user-edit-div" style="margin: 20px;" onkeydown="if(event.keyCode==13){permission.userEdit.save(true);}">
	<p><b><?= TranslateHelper::t('修改用户名') ?></b></p>
	<table style="margin-bottom: 30px; " class="saveFamilyname">
		<tr><td width="80"><?= TranslateHelper::t('用户名') ?></td><td><input class="form-control" type='text' name='edit_familyname' value='<?php echo $user->info->familyname; ?>' /></td><td></td></tr>
		<tr>
			<td colspan='2'><button class="btn btn-primary" type="button" onclick="permission.userEdit.saveFamilyname();"><?= TranslateHelper::t('确认') ?></button></td>
			<td style="display: none; "><input type="hidden" name="edit_user_id" value="<?php echo $user['uid']; ?>" /></td>
		</tr>
	</table>
	
	<form id='permission-user-edit' action='<?php echo \Yii::getAlias('@web');?>/permission/user/save' method='post'>
		<input type="hidden" name="_csrf" value="<?= \Yii::$app->request->getCsrfToken() ?>" />
		<p><b><?= TranslateHelper::t('修改密码') ?></b></p>
		<table>
			<tr><td width="80"><?= TranslateHelper::t('Email') ?></td><td><input class="form-control" type='text' name='email' readonly value='<?php echo $user->email; ?>' /></td><td></td></tr>
			<!-- <tr style="display: none;"><td><?= TranslateHelper::t('用户名') ?></td><td><input class="form-control" type='text' name='familyname' value='<?php echo $user->info->familyname; ?>' /></td><td></td></tr> -->
			<tr><td><span style='color:red'>*</span><?= TranslateHelper::t('原密码') ?></td><td><input class="form-control" type='password' name='formerpassword' /></td><td></td></tr>
			<tr><td><span style='color:red'>*</span><?= TranslateHelper::t('新密码') ?></td><td><input class="form-control" type='password' name='password' /></td><td></td></tr>
			<tr><td><span style='color:red'>*</span><?= TranslateHelper::t('确认新密码') ?></td><td><input class="form-control" type='password' name='repassword' /></td><td></td></tr>
			<tr>
				<td colspan='2'><button class="btn btn-primary" type="button" onclick="permission.userEdit.save(true);"><?= TranslateHelper::t('确认') ?></button></td>
			</tr>
		</table>
	
	    <input type="hidden" name="user_id" value="<?php echo $user['uid']; ?>" />
	    <input type='hidden' name='user_edit_label' value=2 />
	</form>
</div>
