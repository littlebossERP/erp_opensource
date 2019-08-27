<?php

use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\UserHelper;

//$this->registerJs("brand.list.initFormValidate();",  \yii\web\View::POS_READY);
?>
<div class="brand-view">
	<form id="brand_info_form">
	    <table class="table table-striped table-bordered detail-view">
	    	<?php if(isset($tt) && $tt=='edit'){ ?>
	   		<tr style="display:none;">
	    		<th>Brand ID</th><td><input type="hidden" class="form-control" name="brand_id" value="<?=$model->brand_id?>" readonly></td>
	    	</tr>
	    	<?php } ?>
			<tr>
				<th><?=TranslateHelper::t('品牌名称') ?></th><td><input type="text" class="form-control" name="name" value="<?=$model->name?>" ></td>
			</tr>
			<tr>
				<th><?=TranslateHelper::t('备注') ?></th><td><input type="text" class="form-control" name="comment" value="<?=$model->comment?>" ></td>
			</tr>
			<tr>
				<th><?=TranslateHelper::t('额外信息') ?></th><td><input type="text" class="form-control" name="addi_info" value="<?=$model->addi_info?>" ></td>
			</tr>
			<tr>
				<th><?=TranslateHelper::t('修改人') ?></th>
				<td>
					<input type="text" class="form-control" name="capture_user_name" value="<?=UserHelper::getFullNameByUid($model->capture_user_id)?>" readonly>
					
				</td>
			</tr>
		</table>
	</form>
	<p style="text-align: center;">
	    <button type="button" class="btn btn-success" id="brand-save-btn" onclick="brand.list.saveBrand()" ><?=TranslateHelper::t('保存') ?></button>
    </p>
</div>
