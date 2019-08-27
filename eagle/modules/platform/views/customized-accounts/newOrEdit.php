<?php
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/CustomizedAccounts.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("platform.CustomizedAccountsNewOrEdit.initBtn();" , \yii\web\View::POS_READY);

?>
<style>
.PaypalAccountInfo .modal-body {
	max-height: 700px;
	overflow-y: auto;
}
</style>

<div style="padding:10px 15px 0px 15px;heig">
	<form id="platform-CustomizedAccounts-form" action="<?php echo \Yii::getAlias('@web');?>/platform/customized-accounts/save" method="post">
		 <input class="form-control" name="mode" type=hidden value="<?=$mode; ?>" />
	<?php if ($mode<>"new"){ ?>
	    <input class="form-control" name="uid" type=hidden value="<?php echo $CustomizedData['uid']; ?>" />
	    <input class="form-control" name="site_id" type=hidden value="<?php echo $CustomizedData['site_id']; ?>" />
	<?php } ?>    
	
		<div style="margin-top:15px;border:1px solid #cccccc;padding:10px 20px 10px 20px;background-color:#fafafa;border-radius:5px">
			<div style="font-size: 19px;font-weight:bold;text-align: center;"><label><?= TranslateHelper::t('自定义店铺  账号信息设置') ?></label></div>
			<table>
				<tr style="color:blue;font-size:16px;">
					<td colspan="2">
						绑定自定义店铺，用于管理 小老板系统未对接的平台的订单。
					</td>
				</tr>
				   
				<tr>
					<td style="font-size:16px;"><?= TranslateHelper::t('自定义店铺名:') ?></td>
					<td>
						<input class="form-control" type="text" name="store_name" value="<?=$CustomizedData['store_name'] ?>" style="width:180px;display:inline;" <?php if ($mode=="view") echo "disabled"; ?> />
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				
				<tr style="color:blue;font-size:14px;">
					<td colspan="2">
						店铺名是自定义值，相当于下“自定义店铺账号”的别名，用于小老板系统内使用。
					</td>
				</tr>
				
				<tr>
					<td style="font-size:16px;"><?= TranslateHelper::t('自定义店铺账号:') ?></td>
					<td>
						<?php if( $mode <> "new"):?>
						<input class="form-control" type="text" name="user_name" value="<?=$CustomizedData['username'] ?>" style="width:180px;display:inline;" readonly />
						<?php else:?>
						<input class="form-control" type="text" name="user_name" style="width:180px;display:inline;" />
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				
				<tr style="color:blue;font-size:14px;">
					<td colspan="2">
						自定义店铺账号是您销售平台的账号，小老板订单中的销售账号使用的是该值。一旦保存后不能修改。
					</td>
				</tr>
				<tr style="color:red;font-size:14px;">
					<td colspan="2">
						自定义店铺名 和 自定义店铺账号一旦使用后，后面再新建绑定的时候则不能再使用。
					</td>
				</tr>
				
				<tr>
					<td style="font-size:16px;"><?= TranslateHelper::t('是否启用 ：') ?></td>
					<td style="font-size:16px;"><select class="form-control" style="width: auto;" name="is_active" <?php if ($mode=="view") echo "disabled"; ?>>
						   <option value="1" <?php if ($mode<>"new" and $CustomizedData["is_active"]=='1') echo "selected";  ?>><?= TranslateHelper::t('是') ?></option>
						   <option value="0" <?php if ($mode<>"new" and $CustomizedData["is_active"]=='0') echo "selected";  ?>><?= TranslateHelper::t('否') ?></option></select></td>
				</tr>
			</table>
        </div>
	</form>
	
</div>
<script>
</script>