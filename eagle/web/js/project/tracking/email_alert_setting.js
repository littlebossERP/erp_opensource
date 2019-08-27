
/**
 +------------------------------------------------------------------------------
 *自动邮件提醒设置界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		tracker
 * @subpackage  Exception
 * @author		lkh <kanghua.li@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof EmailAlertSetting === 'undefined')  EmailAlertSetting = new Object();
EmailAlertSetting = {
	'init':function(){
		$('[name=not_query_day]:text , [name=shipping_timeout_day]:text').blur(function(){
			if (! /^[1-9]\d*$/.test(this.value)){
				bootbox.alert($("#tip_day_format_error").val());
			}
		});
	},
	
	'SaveSetting':function(){
		if (! /^[1-9]\d*$/.test($('[name=not_query_day]:text').val())){
			bootbox.alert($("#tip_day_format_error").val());
			return false;
		}
		
		if (! /^[1-9]\d*$/.test($('[name=shipping_timeout_day]:text').val())){
			bootbox.alert($("#tip_day_format_error").val());
			return false;
		}
		
		
		
		if ($('[name=custom_email]:text').val() == "" && $("input:checked").length >0){
			bootbox.alert($("#tip_email_empty").val());
			return false;
		}
		$.ajax({
			type: "POST",
			dataType: 'json',
			url:'/tracking/tracking/save-email-alert-setting', 
			data: $("form").serialize(),
			success: function (result) {
				if (result.success){
					bootbox.alert(result.message);
				}else{
					bootbox.alert(result.message);
				}
				return true;
			},
			error :function () {
				return false;
			}
		});
	}
	
}

$(function(){
	EmailAlertSetting.init();
})
