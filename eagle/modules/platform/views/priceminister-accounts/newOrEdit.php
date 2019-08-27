<?php
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\SysBaseInfoHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/PriceMinisterAccounts.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("platform.PriceMinisterAccountsNewOrEdit.initBtn();" , \yii\web\View::POS_READY);

?>
<style>
.cdiscountAccountInfo .modal-body {
	max-height: 700px;
	overflow-y: auto;
}
</style>

<div style="padding:10px 15px 0px 15px;heig">
	<form id="platform-PriceMinisterAccounts-form" action="<?php echo \Yii::getAlias('@web');?>/platform/priceminister-accounts/save" method="post">
	
	<?php if ($mode<>"new"){ ?>
	    <input class="form-control" name="priceminister_uid" type=hidden value="<?php echo $PriceministerData['uid']; ?>" />
		<input class="form-control" name="priceminister_id" type=hidden value="<?php echo $PriceministerData['site_id']; ?>" />
	<?php } ?>    
	
		<div style="margin-top:15px;border:1px solid #cccccc;padding:10px 20px 10px 20px;background-color:#fafafa;border-radius:5px">
			<div style="font-size: 19px;font-weight:bold"><label><?= TranslateHelper::t('Priceminister 账号信息设置') ?></label></div>
			<table>        
				<tr>
					<td style="font-size:14px;"><?= TranslateHelper::t('店铺名称(自定义):') ?></td>
					<td>
						<?php if( $mode <> "new"):?>
						<input class="form-control" type="text" name="store_name" value="<?=$PriceministerData['store_name'] ?>" style="width:180px;display:inline;" <?php if ($mode=="view") echo "disabled"; ?> />
						<?php else:?>
						<input class="form-control" type="text" name="store_name" style="width:180px;display:inline;" />
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				
				<tr id="username_tr">
					<td style="font-size:14px;">
						<?= TranslateHelper::t('Priceminister平台登录名') ?><span style="color:red;">(必须正确填写,一经确定不能更改)</span>
					</td>
					<td style="min-width: 200px;">
						<?php if( $mode <> "new"):?>
						<input class="form-control" name="username" type="text" value="<?=$PriceministerData['username'] ?>" style="width:180px;display:inline;" readonly />
						<?php else:?>
						<input class="form-control" name="username" type="text" style="width:180px;display:inline;" value=""/>
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				<tr id="token_tr">
					<td style="font-size:14px;">
						<?= TranslateHelper::t('Priceminister账号对应token') ?>
					</td>
					<td style="min-width: 200px;">
						<?php if( $mode <> "new"):?>
						<input class="form-control" name="token" type="text" value="<?=$PriceministerData['token'] ?>" style="width:180px;display:inline;" <?php if ($mode=="view") echo "readonly"; ?> />
						<?php else:?>
						<input class="form-control" name="token" type="text" style="width:180px;display:inline;" value=""/>
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				
				<tr style="color:blue;font-size:14px;"><td colspan="2"><label><?= TranslateHelper::t('店铺名称并不是Priceminister提供的信息，是小老板内部管理该店铺的展示名称，可以是任意名字(但同一店铺名称不能重复)。') ?></label></td></tr>
				<tr><td colspan="2" style="font-size:16px;font-weight:600;padding:5px 0px;"><?= TranslateHelper::t('如不清楚如何获得token，') ?><a target="_blank" href="<?=SysBaseInfoHelper::getHelpdocumentUrl('faq_57.html')?>" style="font-size:16px;"><?= TranslateHelper::t('请点击这里') ?></a></td></tr>
				<tr><td colspan="2" style="font-size:14px;"><?= TranslateHelper::t('请正确填写您的登录名和token，否则不会从Priceminister同步到任何信息。') ?></td></tr>
				<tr><td colspan="2" style="font-size:14px;color:red"><?= TranslateHelper::t('如果您的PM账号下有多个账号，此时填写的 "Priceminister平台登录名" 则需要填该子账号的店铺名。') ?></td></tr>
				<tr><td colspan="2" style="font-size:14px;"><span style="color:red;"><?= TranslateHelper::t('一旦您修改了token，您需要把修改也更新到小老板来，否则不能再同步到任何Priceminister信息！') ?></span></td></tr>
				<tr>
					<td style="font-size:14px;"><?= TranslateHelper::t('系统同步是否开启:') ?></td>
					<td style="font-size:14px;"><select class="form-control" style="width: auto;" name="is_active" <?php if ($mode=="view") echo "disabled"; ?>>
						   <option value="1" <?php if ($mode<>"new" and $PriceministerData["is_active"]==1) echo "selected";  ?>><?= TranslateHelper::t('是') ?></option>
						   <option value="0" <?php if ($mode<>"new" and $PriceministerData["is_active"]==0) echo "selected";  ?>><?= TranslateHelper::t('否') ?></option></select></td>
				</tr>
				
			</table>
        </div>
	</form>
	
</div>
<script>
</script>