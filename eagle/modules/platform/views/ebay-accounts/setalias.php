<?php
use eagle\modules\util\helpers\TranslateHelper;
?>
<div id="div_aliexpress_set_alias">
	<form id="platform-ebay-setalias-form" method="post">
		<input type="hidden" name="ebay_uid"  value="<?php echo $account['ebay_uid']?>"/>
		<div class="alert alert-info" role="alert">
			<strong><?=TranslateHelper::t('账号')?></strong>：<?php echo $account['selleruserid']?>
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