<?php
use eagle\modules\util\helpers\TranslateHelper;
?>
<div id="div_aliexpress_set_alias">
	<form id="platform-wish-setalias-form" method="post">
		<input type="hidden" name="site_id"  value="<?php echo $account['site_id']?>"/>
		
		<div class="box-row">
			<label class="item" for="storeNameAlias"><?=TranslateHelper::t('账号别名')?></label>
			<div class="ipt_box">
				<input type="text" id="storeNameAlias" name="store_name_alias"  class="form-control" data-maxlength="32" value="<?=($account['store_name_alias'] == '') ? $account['store_name'] : $account['store_name_alias'] ?>">
			</div>
		</div>
	</form>
	<div class="text-center">
	<button type="button" class="iv-btn btn-success" id="btn_ok"><?=TranslateHelper::t('保存')?></button>
	<button type="button" class="iv-btn btn-default" id="btn_cancel"><?=TranslateHelper::t('取消')?></button>
	</div>
</div>