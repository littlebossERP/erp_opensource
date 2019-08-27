<?php
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJsFile(\Yii::getAlias('@web')."/js/project/platform/BonanzaAccountsNewOrEdit.js", ['depends' => ['yii\web\JqueryAsset']]);
$this->registerJs("platform.BonanzaAccountsNewOrEdit.initBtn();" , \yii\web\View::POS_READY);
if(isset($success)){
    if(!$success){//检查是否获取token以及url成功
        $checkStatus = true;
    }else{
        $checkStatus = false;
    } 
}

?>
<style>
.bonanzaAccountInfo .modal-body {
	max-height: 700px;
	overflow-y: auto;
}
</style>

<div style="padding:10px 15px 0px 15px;heig">
	<form id="platform-BonanzaAccounts-form" action="<?php echo \Yii::getAlias('@web');?>/platform/bonanza-accounts/save" method="post">
	<input class="form-control" name="token" type=hidden value="<?php echo !empty($token)?$token:(!empty($BonanzaData['token'])?$BonanzaData['token']:'');?>" />
	<?php if ($mode<>"new"){ ?>
	    <input class="form-control" name="bonanza_uid" type=hidden value="<?php echo $BonanzaData['uid']; ?>" />
		<input class="form-control" name="bonanza_id" type=hidden value="<?php echo $BonanzaData['site_id']; ?>" />
		<input class="form-control" name="store_name" type=hidden value="<?php echo $BonanzaData['store_name']; ?>" />
	<?php } ?>    
	<?php if($mode == "new"):?>
	   <input class="form-control" name="link_check" type=hidden value="" >
	<?php endif;?>
	<?php if($mode == "edit"):?>
	   <input class="form-control" name="link_check" type=hidden value="Yes" >
	<?php endif;?>
		<div style="margin-top:15px;border:1px solid #cccccc;padding:10px 20px 10px 20px;background-color:#fafafa;border-radius:5px">
			<div style="font-size: 19px;font-weight:bold"><label><?= TranslateHelper::t('Bonanza api账号信息设置') ?></label></div>
			<table>
			    <?php if($mode == "new"&&$checkStatus == true):?>
			    <tr>
					<td style="font-size:14px;"><?= TranslateHelper::t('绑定信息:') ?></td>
					<td>
						<span style="color:red;margin-left:3px">获取token失败,<?php echo !empty($message)?$message:''?></span>
					</td>
				</tr>
				<?php endif;?>        
				<tr>
					<td style="font-size:14px;"><?= TranslateHelper::t('Bonanza店铺名称(一旦保存不能修改):') ?></td>
					<td>
						<?php if( $mode <> "new"):?>
						<input class="form-control" type="text" name="store_name" value="<?=$BonanzaData['store_name'] ?>" style="width:180px;display:inline;" disabled />
						<?php else:?>
						<input class="form-control" type="text" name="store_name" style="width:180px;display:inline;" <?php if ($checkStatus) echo "disabled"; ?>/>
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				
				<tr id="api_token_tr" style="display:table-row">
					<td style="font-size:14px;">
						<?= TranslateHelper::t('Bonanza Token:') ?>
					</td>
					<td style="min-width: 200px;">
						<?php if( $mode == "edit"):?>
						<input class="form-control" type="text" value="<?=$BonanzaData['token'] ?>" style="width:180px;display:inline;" readonly/>
						<?php else:?>
						<input class="form-control" type="text" style="width:180px;display:inline;" value="<?php echo !empty($token)?$token:'';?>" readonly />
						<?php endif;?>
						<span style="color:red;margin-left:3px">*</span>
					</td>
				</tr>
				
				<tr id="api_link_tr" style="display:table-row">
					<td style="font-size:14px;">
						<?= TranslateHelper::t('Go to Bonanza 帐号授权:') ?>
					</td>
					<td style="min-width: 200px;">
						<?php if( $mode == "edit"):?>
						<input class="btn btn-warning btn-sm" name="link_button" type="button" value="Bonanza授权" style="width:180px;display:inline;" disabled/>
						<?php else:?>
						<a href="<?php echo !empty($url)?$url:'';?>" target="_blank"><input class="btn btn-warning btn-sm" name="link_button" type="button" style="width:180px;display:inline;" value="Bonanza授权" <?php if ($checkStatus) echo "disabled"; ?>/></a>
						<?php endif;?>
					</td>
				</tr>
				
				<!--  
				<tr style="color:blue;font-size:14px;"><td colspan="2"><label><?= TranslateHelper::t('店铺名称并不是Bonanza提供的接口信息，是小老板内部管理该店铺的展示名称，可以是任意名字。') ?></label></td></tr>
				
				<tr><td colspan="2" style="font-size:16px;font-weight:600;padding:5px 0px;"><span style="color:red;"><?= TranslateHelper::t('推荐使用Bonanza接口账号和对应的密码进行绑定！') ?></span><?= TranslateHelper::t('如不清楚如何获得接口账号和密码，') ?><a target="_blank" href="/images/bonanza/bonanza-bindingHelpe.png" style="font-size:16px;"><?= TranslateHelper::t('请点击这里') ?></a></td></tr>
				<tr><td colspan="2" style="font-size:14px;padding:5px 0px;"><?= TranslateHelper::t('如果您由于某些原因不能获取接口账号和密码，或暂时不想用该方式验证绑定，你也可以用Bonanza的平台登录名和密码进行绑定。') ?></td></tr>
				<tr><td colspan="2" style="font-size:14px;"><?= TranslateHelper::t('请正确填写您选择的绑定方式的登录名和密码，否则会绑定失败。') ?></td></tr>
				<tr><td colspan="2" style="font-size:14px;"><span style="color:red;"><?= TranslateHelper::t('无论您使用哪一种方式进行绑定，一旦您修改了该方式的密码，您需要把修改也更新到小老板来，否则不能再同步到任何Bonanza信息！') ?></span></td></tr>
				-->
				<tr>
					<td style="font-size:14px;"><?= TranslateHelper::t('系统同步是否开启:') ?></td>
					<td style="font-size:14px;"><select class="form-control" style="width: auto;" name="is_active" <?php if ($mode=="view") echo "disabled"; ?>>
						   <option value="1" <?php if ($mode<>"new" and $BonanzaData["is_active"]==1) echo "selected";  ?>><?= TranslateHelper::t('是') ?></option>
						   <option value="0" <?php if ($mode<>"new" and $BonanzaData["is_active"]==0) echo "selected";  ?>><?= TranslateHelper::t('否') ?></option></select></td>
				</tr>
				<tr>
					<td style="font-size:14px;"><?= TranslateHelper::t('订单是否自动接受:') ?></td>
					<td style="font-size:14px;"><select class="form-control" style="width: auto;" name="is_auto_accept">
						   <option value="1" <?php if ($mode<>"new" and $BonanzaData["is_auto_accept"]==1) echo "selected";  ?>><?= TranslateHelper::t('是') ?></option>
						   <option value="0" <?php if ($mode<>"new" and $BonanzaData["is_auto_accept"]==0) echo "selected";  ?>><?= TranslateHelper::t('否') ?></option></select></td>
				</tr>
				<tr><td colspan="2" style="font-size:14px;"><span style="color:red;"><?= TranslateHelper::t('订单自动接受：Bonanza订单买家下单后需要卖家接受订单，买家才会付款，进行订单操作。该设置订单是在买家下单后知道接受该订单，从而缩短订单的流程时间和卖家订单操作时间。') ?></span></td></tr>
			</table>
        </div>
	</form>
	
</div>
