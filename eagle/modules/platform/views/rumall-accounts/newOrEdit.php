<?php
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/RumallAccountsNewOrEdit.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("platform.RumallAccountsNewOrEdit.initBtn();" , \yii\web\View::POS_READY);
?>
<style>
.rumallAccountInfo .modal-body {
	max-height: 700px;
	overflow-y: auto;
}
</style>

<div style="padding:10px 15px 0px 15px;">
	<form id="platform-RumallAccounts-form" action="<?php echo \Yii::getAlias('@web');?>/platform/rumall-accounts/save" method="post">
	<input class="form-control" name="token" type=hidden value="<?php echo !empty($token)?$token:(!empty($RumallData['token'])?$RumallData['token']:'');?>" />
	<?php if ($mode<>"new"){ ?>
	    <input class="form-control" name="rumall_uid" type=hidden value="<?php echo $RumallData['uid']; ?>" />
		<input class="form-control" name="rumall_id" type=hidden value="<?php echo $RumallData['site_id']; ?>" />
		<input class="form-control" name="store_name" type=hidden value="<?php echo $RumallData['store_name']; ?>" />
		<input class="form-control" name="company_code" type=hidden value="<?php echo $RumallData['company_code']; ?>" />
	<?php } ?>    
		<div style="margin-top:15px;border:1px solid #cccccc;padding:10px 20px 10px 20px;background-color:#fafafa;border-radius:5px">
			<div style="font-size: 19px;font-weight:bold"><label><?= TranslateHelper::t('Rumall api账号信息设置') ?></label></div>
			<table>
				<tr>
					<td style="font-size:14px;"><?= TranslateHelper::t('Rumall店铺名称(一旦保存不能修改):') ?></td>
					<td>
						<?php if( $mode <> "new"):?>
						<input class="form-control" type="text" name="store_name" value="<?=$RumallData['store_name'] ?>" style="width:180px;display:inline;" disabled />
						<?php else:?>
						<input class="form-control" type="text" name="store_name" style="width:180px;display:inline;"/>
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				<tr>
					<td style="font-size:14px;"><?= TranslateHelper::t('Rumall 货主编码:') ?></td>
					<td>
					    <?php if( $mode <> "new"):?>
					    <input class="form-control" type="text" name="company_code" value="<?=$RumallData['company_code'] ?>" style="width:180px;display:inline;" disabled/>
						<?php else:?>
						<input class="form-control" type="text" name="company_code" value="" style="width:180px;display:inline;"/>
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				<tr id="api_token_tr" style="display:table-row">
					<td style="font-size:14px;">
						<?= TranslateHelper::t('Rumall Checkword:') ?>
					</td>
					<td style="min-width: 200px;">
						<input class="form-control" type="text" name="token" value="<?=!empty($RumallData['token'])?$RumallData['token']:'';?>" style="width:180px;display:inline;"/>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				<tr>
					<td style="font-size:14px;"><?= TranslateHelper::t('系统同步是否开启:') ?></td>
					<td style="font-size:14px;"><select class="form-control" style="width: auto;" name="is_active" <?php if ($mode=="view") echo "disabled"; ?>>
						   <option value="1" <?php if ($mode<>"new" and $RumallData["is_active"]==1) echo "selected";  ?>><?= TranslateHelper::t('是') ?></option>
						   <option value="0" <?php if ($mode<>"new" and $RumallData["is_active"]==0) echo "selected";  ?>><?= TranslateHelper::t('否') ?></option></select></td>
				</tr>
				
				<tr style="color:blue;font-size:14px;"><td colspan="2"><label><?= TranslateHelper::t('店铺名称并不是Rumall提供的接口信息，是小老板内部管理该店铺的展示名称，可以是任意名字。') ?></label></td></tr>
				<tr><td colspan="2" style="font-size:16px;font-weight:600;padding:5px 0px;"><span style="color:red;"><?= TranslateHelper::t('获取Rumall货主编码以及Checkword方法：') ?></span></td></tr>
				<tr><td colspan="2" style="font-size:14px;padding:0px 0px 5px 0px;"><?= TranslateHelper::t('入驻Rumall商城，签订合同，确认使用第三方ERP（非使用Rumall商家后台），可联系Rumall招商同事获取。') ?></td></tr>
				
			</table>
        </div>
	</form>
	
</div>
