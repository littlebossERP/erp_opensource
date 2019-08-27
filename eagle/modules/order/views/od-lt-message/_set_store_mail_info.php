<?php 
use eagle\modules\util\helpers\TranslateHelper;

$this->registerJs("$.initQtip();" , \yii\web\View::POS_READY);
?>
<style>
.set-store-mail-win .modal-dialog{
	width:760px;
}
#store_mail_info_tb th,#store_mail_info_tb td{
	vertical-align: middle;
	text-align: center;
	padding:4px 4px;
	border:1px solid;
}
</style>
<form class="form-horizontal" role="form" id="store_mail_info">
	<input type="hidden" name="platform" value="<?=$platform?>">
	<table id="store_mail_info_tb" class="table" style="max-width:700px;">
		<tr>
			<th>店铺名</th>
			<?php if($platform=='amazon'){ ?>
			<th>Merchant Id</th>
			<?php } ?>
			<th>eMail地址<span qtipkey="platform_store_email_address_validat"></span></th>
			<th>发送人名称<span qtipkey="platform_store_email_name_validat"></span></th>
		</tr>
		<?php foreach($listStoreMailInfo as $store_name=>$info){ ?>
		<tr>
		<?php 
		if(in_array($platform,['amazon'])){
			$store_name = $info['store_name'];
		}
		?>
			<td><?=$store_name?><input type="hidden" name="store_name[]" value="<?=$store_name?>"></td>
			<?php if($platform=='amazon'){ ?>
			<td><?=$info['merchant_id']?><input type="hidden" name="addi_key[]" value="<?=$info['merchant_id']?>"></td>
			<?php } ?>
			<td><input class="eagle-form-control" name="mail_address[]" value="<?=empty($info['mail_address'])?'':$info['mail_address']?>"><span style="color:red">*</span></td>
			<td><input class="eagle-form-control" name="name[]" value="<?=empty($info['mail_sender_name'])?'':$info['mail_sender_name']?>"></td>
		</tr>
		<?php } ?>
	</table>
</form>
