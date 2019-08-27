<?php
use eagle\modules\util\helpers\TranslateHelper;
?>
<div id="div_aliexpress_set_alias">
	<form id="platform-aliexpress-setalias-form" action="<?php echo \Yii::getAlias('@web');?>/platform/aliexpress-accounts/save-alias" method="post">
		<input type="hidden" name="sellerloginid"  value="<?php echo $account['sellerloginid']?>"/>
		<input type="hidden" name="aliexpress_uid"  value="<?php echo $account['aliexpress_uid']?>"/>
		<div class="alert alert-info" role="alert">
			<strong><?=TranslateHelper::t('账号')?></strong>：<?php echo $account['sellerloginid']?>
		</div>
		
		
		<div class="box-row">
			<label class="item" for="storeName"><?=TranslateHelper::t('账号别名')?></label>
			<div class="ipt_box">
				<input type="text" id="storeName" name="store_name"  class="form-control" data-maxlength="32" value="<?php echo $account['store_name']?>">
				
			</div>
			
		</div>
	</form>
	<div class="text-center">
	<button type="button" class="iv-btn btn-success" id="btn_ok"><?=TranslateHelper::t('保存')?></button>
	<button type="button" class="iv-btn btn-default" id="btn_cancel"><?=TranslateHelper::t('取消')?></button>
	</div>
</div>