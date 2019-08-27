<?php

use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\helpers\UserHelper;

$this->title = $model->name;

?>
<div class="brand-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <button type="button" class="btn btn-info" id="toViewMethod-btn" onclick="brand.list.viewMethod()" style="display: none;"><?=TranslateHelper::t('查看') ?></button>
    	<button type="button" class="btn btn-info" id="toEditMethod-btn" onclick="brand.list.editMethod()" style="display: block;"><?=TranslateHelper::t('编辑') ?></button>
	</p>
	<form id="brand_info_form">
	    <table class="table table-striped table-bordered detail-view">
	    	<tr style="display:none;">
	    		<th>Brand ID</th><td><input type="hidden" class="form-control" name="brand_id" value="<?=$model->brand_id?>" readonly></td>
	    	</tr>
			<tr>
				<th><?=TranslateHelper::t('品牌名称') ?></th><td><input type="text" class="form-control" name="name" value="<?=$model->name?>" readonly></td>
			</tr>
			<tr>
				<th><?=TranslateHelper::t('备注') ?></th><td><input type="text" class="form-control" name="comment" value="<?=$model->comment?>" readonly></td>
			</tr>
			<tr>
				<th><?=TranslateHelper::t('额外信息') ?></th><td><input type="text" class="form-control" name="addi_info" value="<?=$model->addi_info?>" readonly></td>
			</tr>
			<tr>
				<th><?=TranslateHelper::t('修改人') ?></th>
				<td>
					<input type="text" class="form-control" name="capture_user_name" value="<?=UserHelper::getFullNameByUid($model->capture_user_id)?>" readonly>
					
				</td>
			</tr>
			<tr>
				<th><?=TranslateHelper::t('创建时间') ?></th>
				<td>
					<input type="text" class="form-control" name="create_time" value="<?=$model->create_time?>" readonly placeholder="<?= TranslateHelper::t('留空则保存为当前时间')?>">
				</td>
			</tr>
			<tr>
				<th><?=TranslateHelper::t('最后修改时间') ?></th>
				<td>
					<input type="text" class="form-control" name="update_time" value="<?=$model->update_time?>" readonly>
				</td>
			</tr>
		</table>
	</form>
	<p style="text-align: center;">
	    <button type="button" class="btn btn-success" id="brand-save-btn" onclick="brand.list.saveBrand()" style="display: none;"><?=TranslateHelper::t('保存') ?></button>
    	<button type="button" class="btn btn-danger" id="brand-del-btn" onclick="brand.list.deleteBrand(<?=$model->brand_id ?>,'<?=htmlentities($model->name) ?>')" style="display: inline-block;"><?=TranslateHelper::t('删除') ?></button>
    </p>
</div>
