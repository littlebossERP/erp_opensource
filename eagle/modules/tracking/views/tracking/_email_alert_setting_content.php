<?php
use yii\helpers\Html;
use eagle\modules\util\helpers\TranslateHelper;
?>
<div class="panel panel-primary">
	<div class="panel-body">
	<p><?= TranslateHelper::t('物流查询助手会持续监测你提交的物流号，如果物流号符合某些条件，本系统将会每天自动发送异常物流报告给你');?></p>
	<p><?= TranslateHelper::t('请选择以下需要发送的报告内容：');?></p>
	<form name="frm_email_alert_setting">
		<ul class="list-unstyled">
			
			<li>
				<input type="checkbox"  name="all_exception_alert" value = "Y" <?=((empty($config['all_exception_alert']))?"":'checked="checked" ');?>><?= TranslateHelper::t('1. 包裹状态是所有异常');?>
				<ul class="list-unstyled menu_lev2">
					<li><input type="checkbox" name="arrived_alert"  value = "Y" <?=((empty($config['arrived_alert']))?"":'checked="checked" ');?>><?= TranslateHelper::t('1.1 包裹状态是 “到达待取”');?></li>
					<li><input type="checkbox" name="failure_delivery_alert"  value = "Y" <?=((empty($config['failure_delivery_alert']))?"":'checked="checked" ');?>><?= TranslateHelper::t('1.2 包裹状态是 “递送失败”');?></li>
				</ul>
			</li>
			<li>
				<input type="checkbox" name="not_query_alert" <?=((empty($config['not_query_alert']))?"":'checked="checked" ');?>><?= TranslateHelper::t('2. 包裹状态持续 ').
			'<input type="text" name="not_query_day" class="date_input" value="'.((empty($config['not_query_day']))?"":$config['not_query_day']).'">'.
			TranslateHelper::t(' 天，还查询不到，可能是交运失败，例如安检不通过，超重，超长等');?></li>
			<li><input type="checkbox" name="shipping_timeout_alert" value="Y" <?=((empty($config['shipping_timeout_alert']))?"":'checked="checked" ');?>><?= TranslateHelper::t('3. 包裹状态持续 ').
			'<input type="text" name="shipping_timeout_day" class="date_input"  value="'.((empty($config['shipping_timeout_day']))?"":$config['shipping_timeout_day']).'">'.
			TranslateHelper::t(' 天，停留在 “运输途中”，属于运输过久，可能是被目的地海关扣押，或者递送人员罢工等');?></li>
			<li>
				<!-- <input type="checkbox" name ="customer_email" value="Y" <?=((empty($config['customer_email']))?"":'checked="checked" ');?>>-->
				<?= TranslateHelper::t('本系统将会每天发送邮件报告到以下邮箱地址：');?>
				<input type="text" name="custom_email" value="<?= ((empty($config['custom_email']))?"":$config['custom_email'])?>">
				<span class="text-danger"><?= TranslateHelper::t('此功仅限VIP用户使用')?></span>
			</li>
		</ul>
	</div>
	</form>
	
	<div class="panel-footer">
		<input class="btn btn-default" type="button" value="<?= TranslateHelper::t('保存设置')?>" onclick="EmailAlertSetting.SaveSetting()">
	</div>
</div>

<input type="hidden" id="tip_day_format_error" value="<?= TranslateHelper::t('日期间隔格式不正确');?>"/>
<input type="hidden" id="tip_email_empty" value="<?= TranslateHelper::t('请输入邮箱地址');?>"/>


