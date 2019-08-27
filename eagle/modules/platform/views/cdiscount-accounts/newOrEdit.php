<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\SysBaseInfoHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/CdiscountAccountsNewOrEdit.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("platform.CdiscountAccountsNewOrEdit.initBtn();" , \yii\web\View::POS_READY);

?>
<style>
.cdiscountAccountInfo .modal-body {
	max-height: 700px;
	overflow-y: auto;
}
</style>

<div style="padding:10px 15px 0px 15px;heig">
	<form id="platform-CdiscountAccounts-form" action="<?php echo \Yii::getAlias('@web');?>/platform/cdiscount-accounts/save" method="post">
	
	<?php if ($mode<>"new"){ ?>
	    <input class="form-control" name="cdiscount_uid" type=hidden value="<?php echo $CdiscountData['uid']; ?>" />
		<input class="form-control" name="cdiscount_id" type=hidden value="<?php echo $CdiscountData['site_id']; ?>" />
		<input class="form-control" name="auth_type" type=hidden value="0" />
	<?php } ?>    
	
		<div style="margin-top:15px;border:1px solid #cccccc;padding:10px 20px 10px 20px;background-color:#fafafa;border-radius:5px">
			<div style="font-size: 19px;font-weight:bold"><label><?= TranslateHelper::t('Cdiscount Api账号信息设置') ?></label></div>
			<table>        
				<tr>
					<td style="font-size:14px;"><?= TranslateHelper::t('店铺名称(自定义):') ?></td>
					<td>
						<?php if( $mode <> "new"):?>
						<input class="form-control" type="text" name="store_name" value="<?=$CdiscountData['store_name'] ?>" style="width:180px;display:inline;" <?php if ($mode=="view") echo "disabled"; ?> />
						<?php else:?>
						<input class="form-control" type="text" name="store_name" style="width:180px;display:inline;" />
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				
				<tr id="api_username_tr"">
					<td style="font-size:14px;">
						<?= TranslateHelper::t('Cdiscount Api账号') ?><span style="color:red;"></span>
					</td>
					<td style="min-width: 200px;">
						<?php if( $mode <> "new"):?>
						<input class="form-control" name="api_username" type="text" value="<?=$CdiscountData['api_username'] ?>" style="width:180px;display:inline;" <?php if ($mode=="view") echo "readonly"; ?> />
						<?php else:?>
						<input class="form-control" name="api_username" type="text" style="width:180px;display:inline;" value=""/>
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				
				<tr id="api_password_tr">
					<td style="font-size:14px;">
						<?= TranslateHelper::t('Cdiscount Api账号对应密码') ?><span style="color:red;"></span>
					</td>
					<td style="min-width: 200px;">
						<?php if( $mode <> "new"):?>
						<input class="form-control" name="api_password" type="password" value="<?=$CdiscountData['api_password'] ?>" style="width:180px;display:inline;" <?php if ($mode=="view") echo "readonly"; ?> />
						<?php else:?>
						<input class="form-control" name="api_password" type="password" style="width:180px;display:inline;" value=""/>
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				
				<tr id="platform_account_tr">
					<td style="font-size:14px;"><span><?= TranslateHelper::t('Cdiscount平台账号') ?></span><span style="color:red;">(必须正确填写,一经确定不能更改)</span></td>
					<td style="min-width: 200px;">
						<?php if( $mode <> "new"):?>
						<input class="form-control" name="platform_account" value="<?=$CdiscountData['username'] ?>" style="width:180px;display:inline;" readonly />
						<?php else:?>
						<input class="form-control" name="platform_account" value="" style="width:180px;display:inline;" value=""/>
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				
				<tr style="color:blue;font-size:14px;"><td colspan="2" style="padding-bottom: 5px;"><label><?= TranslateHelper::t('店铺名称并不是Cdiscount提供的接口信息，是小老板内部管理该店铺的展示名称，可以是任意名字。') ?></label></td></tr>
				<tr style="color:blue;font-size:14px;"><td colspan="2" style="padding-bottom: 5px;"><?= TranslateHelper::t('如不清楚如何获得Api账号和密码，') ?><a target="_blank" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('word_list_247_279.html')?>" style="font-size:16px;"><?= TranslateHelper::t('请点击这里') ?></a></td></tr>
				
				<tr><td colspan="2" style="font-size:14px;padding-bottom: 5px;" ><?= TranslateHelper::t('请正确填写您的api账号名和api密码，否则会绑定失败。') ?></td></tr>
				<tr><td colspan="2" style="font-size:14px;padding-bottom: 5px;"><span style="color:red;"><?= TranslateHelper::t('一旦您修改了api密码，您需要把修改也更新到小老板来，否则不能再同步到任何Cdiscount信息！') ?></span></td></tr>
				<tr>
					<td style="font-size:14px;"><?= TranslateHelper::t('系统同步是否开启:') ?></td>
					<td style="font-size:14px;"><select class="form-control" style="width: auto;" name="is_active" <?php if ($mode=="view") echo "disabled"; ?>>
						   <option value="1" <?php if ($mode<>"new" and $CdiscountData["is_active"]==1) echo "selected";  ?>><?= TranslateHelper::t('是') ?></option>
						   <option value="0" <?php if ($mode<>"new" and $CdiscountData["is_active"]==0) echo "selected";  ?>><?= TranslateHelper::t('否') ?></option></select></td>
				</tr>
				
			</table>
        </div>
	</form>
	
</div>
<script>

</script>