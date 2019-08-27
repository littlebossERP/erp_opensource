<?php
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/WishAccountsNewOrEdit.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("platform.WishAccountsNeworedit.initWidget();" , \yii\web\View::POS_READY);

?>
<style>
.imgBlock .modal-dialog{
	width: 1210px;
	height: 500px;
}
</style>

<div style="padding:10px 15px 0px 15px">
	<form id="platform-WishAccounts-form" action="<?php echo \Yii::getAlias('@web');?>/platform/WishAccounts/save" method="post">
	
	<?php if ($mode<>"new"){ ?>
	    <input class="form-control" name="Wish_uid" type=hidden value="<?php echo $WishData['uid']; ?>" />
		<input class="form-control" name="Wish_id" type=hidden value="<?php echo $WishData['site_id']; ?>" />
	<?php } ?>    
	
		<div style="margin-top:15px;border:1px solid #cccccc;padding:10px 20px 10px 20px;background-color:#fafafa;border-radius:5px">
			<div style="font-size: 19px;font-weight:bold"><label><?= TranslateHelper::t('Wish api账号信息设置') ?></label></div>
			<table>        
				<tr>
					<td><?= TranslateHelper::t('店铺名称(自定义):') ?></td>
					<td>
						<?php if( $mode <> "new"):?>
						<input class="form-control" type="text" name="store_name" value="<?=$WishData['store_name'] ?>" style="width:180px;display:inline;" <?php if ($mode=="view") echo "disabled"; ?> />
						<?php else:?>
						<input class="form-control" type="text" name="store_name" style="width:180px;display:inline;" />
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				<!-- 
				<tr>
					<td><?= TranslateHelper::t('商户ID:') ?></td>
					<td>
						<?php if( $mode <> "new"):?>
						<input class="form-control" name="token" type="text" value="<?=$WishData['token'] ?>" style="width:180px;display:inline;" <?php if ($mode=="view") echo "disabled"; ?> /> 
						
						<?php else:?>
						<input class="form-control" name="token" type="text" style="width:180px;display:inline;" />
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				 -->
				<tr style="color:blue"><td colspan="2"><label><?= TranslateHelper::t('店铺名称并不是Wish提供的接口信息，是小老板内部管理该店铺的展示名称，可以是任意名字。') ?></label></td></tr>
				<tr><td td colspan="2"><?= TranslateHelper::t('本页面直接访问wish官网授权页面，并非小老板本地页面，小老板系统无法知晓您的密码，请放心使用。') ?></td></tr>
				<tr><td td colspan="2"><?= TranslateHelper::t('请确保你在授权此Wish账号之前已经在此浏览器退出其他Wish账号的登陆，以免造成Wish账号授权串号问题。') ?></td></tr>
				
				<tr>
					<td><?= TranslateHelper::t('系统同步是否开启:') ?></td>
					<td><select class="form-control" style="width: auto;" name="is_active" <?php if ($mode=="view") echo "disabled"; ?>>
						   <option value="1" <?php if ($mode<>"new" and $WishData["is_active"]==1) echo "selected";  ?>><?= TranslateHelper::t('是') ?></option>
						   <option value="0" <?php if ($mode<>"new" and $WishData["is_active"]==0) echo "selected";  ?>><?= TranslateHelper::t('否') ?></option></select></td>
				</tr>
			</table>
        </div>
	</form>
	
</div>
