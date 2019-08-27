<?php

use eagle\modules\util\helpers\TranslateHelper;
use yii\helpers\Html;
?>
<style>
#neweggAccountInfoForm table tr{
	height: 40px;
}
</style>
<div style="border:1px solid #cccccc;padding:10px 20px 10px 20px;background-color:#fafafa;border-radius:5px">
	<form id="neweggAccountInfoForm" action="">
		<input type="hidden" name="site_id" value="<?=@$info['site_id'] ?>" >
		<table>
			<tr style="color:red">
				<td colspan="2">暂时只支持US的B2C站点！</td>
			</tr>
			<tr>
				<td style="font-size:14px;"><?= TranslateHelper::t('店铺名称：<br>(Newegg登录名或自定义,<br>用于区分多店铺或者多站点)') ?></td>
				<td>
					<input class="iv-input" type="text" name="store_name" value="<?=@$info['store_name'] ?>" style="width:180px;display:inline;" <?php if (isset($info['site_id']) && !empty($info['site_id']) && 0) echo "disabled"; ?> />
					<span style="color:red;margin-left:3px">*</span>
				</td>
			</tr>
			<tr style="color:blue">
				<td colspan="2">Seller ID、Authorization、SecretKey 为接口授权信息，您需要向Newegg客户经理索取。</td>
			</tr>
			<tr>
				<td style="font-size:14px;"><?= TranslateHelper::t('Seller ID:') ?></td>
				<td>
					<input class="iv-input" type="text" name="SellerID" value="<?=@$info['SellerID'] ?>" style="width:180px;display:inline;" />
					<span style="color:red;margin-left:3px">*</span>
				</td>
			</tr>
			<tr style="color:blue">
				<td colspan="2">Authorization可以填小老板的Newegg第三方验证:"b72e2e7e7f514e54a372552756e949ec"</td>
			</tr>
			<tr>
				<td style="font-size:14px;"><?= TranslateHelper::t('Authorization:') ?></td>
				<td>
					<input class="iv-input" type="text" name="Authorization" value="<?=@$info['Authorization'] ?>" style="width:180px;display:inline;" />
					<span style="color:red;margin-left:3px">*</span>
				</td>
			</tr>
			<tr>
				<td style="font-size:14px;"><?= TranslateHelper::t('SecretKey:') ?></td>
				<td>
					<input class="iv-input" type="text" name="SecretKey" value="<?=@$info['SecretKey'] ?>" style="width:180px;display:inline;" />
					<span style="color:red;margin-left:3px">*</span>
				</td>
			</tr>
			<tr>
				<td style="font-size:14px;"><?= TranslateHelper::t('系统同步是否开启:') ?></td>
				<td>
					<?= Html::dropDownList('is_active', @$info['is_active'], [1=>'开启',0=>'关闭'], ['class'=>'iv-input'])?>
					<span style="color:red;margin-left:3px">*</span>
				</td>
			</tr>

		</table>
	
	</form>

</div>

<div class="text-center" style="padding:8px;">
	<a class="iv-btn btn-success btn-lg" onclick="platform.neweggAccountsList.saveNeweggAccountInfo()">保存</a>
	<a class="iv-btn btn-default btn-lg modal-close">取消</a>
</div>