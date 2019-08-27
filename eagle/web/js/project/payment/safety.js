if(typeof safety === 'undefined') safety = new Object();
var wait = 60;
var id_Interval = '';

safety = {
		
	ShowBinding : function(binding){
		if(binding == 1){
			bootbox.alert('更新手机号，请联系客服！');
			return false;
		}
		
		bootbox.dialog({
			title: Translator.t("添加手机绑定"),
			className: "binding_mobile",
			message: $('#dialog_binding_mobile').html(),
			buttons: {
				OK: {
					label: Translator.t("确定"),
					className: "btn-success",
					callback: function(){
						var verifyCode = $('.binding_mobile #verification_code').val().replace(/(^\s*)|(\s*$)/g, "");
						var discount_type = $('[name="discount_type"]:checked').val();
						
						$.showLoading();
						$.ajax({
							type: 'post',
						    cache: 'false',
						    dataType: 'json',
						    data: {
						    		'verifyCode': verifyCode,
						    		'discount_type': discount_type,
						    	},
							url: '/payment/user-account/binding-mobile',
							success: function (result) 
							{
								$.hideLoading();
								if(result.success){
									$.alertBox('<p class="text-warn">绑定成功！</p>');
									window.location.reload();
								}
								else{
									bootbox.alert(result.msg);
								}
							},
							error: function()
							{
								bootbox.alert("出现错误，请联系客服求助...");
								$.hideLoading();
							}
						});
						
						return false;
					}
				},
				Cancel: {
					label: Translator.t("取消"),
					className: "btn-default",
					callback: function(){
						
					}
				},
			}
		});
	},
	
	SendVerification : function(){
		if(id_Interval != ''){
			return false;
		}
		
		var new_mobile = $('.binding_mobile #new_mobile').val().replace(/(^\s*)|(\s*$)/g, "");
		if(new_mobile == undefined || new_mobile == ''){
			bootbox.alert('手机号不能为空！');
			return false;
		}
		if(new_mobile.length != 11){
			bootbox.alert('手机号格式错误！');
			return false;
		}
		
		$('.binding_mobile #a_sent_ver').css('background', 'rgb(204, 204, 204)').css('color', 'rgb(51, 51, 51)').css('cursor', 'not-allowed');
		
		$.ajax({
			type: 'post',
			cache: 'false',
			dataType: 'json',
			data: {
				'new_mobile': new_mobile,
			},
			url: '/payment/user-account/send-verification',
			success: function(result){
				if(result.success){
					wait = 60 + 1;
					safety.GetRTime();
					
					$.alertBox('<p class="text-warn">发送成功！</p>');
					
					//每隔一秒运行
					id_Interval = window.setInterval("safety.GetRTime()", 1000);
				}
				else{
					bootbox.alert(result.msg);
					
					if(result.time > 0){
						wait = 60 - result.time + 1;
						safety.GetRTime();
						
						id_Interval = window.setInterval("safety.GetRTime()", 1000);
					}
					else{
						$('.binding_mobile #a_sent_ver').html('重新发送 ');
						$('.binding_mobile #a_sent_ver').css('background', '').css('color', '').css('cursor', '');
					}
				}
			},
			error: function(){
				bootbox.alert('出现错误，请联系客服求助...');
				$.hideloading();
				return false;
			}
		});
	},
	
	GetRTime : function(){
		wait--;

		if(wait < 1){
			clearInterval(id_Interval);
			id_Interval = '';
			
			$('.binding_mobile #a_sent_ver').html('重新发送 ');
			$('.binding_mobile #a_sent_ver').css('background', '').css('color', '').css('cursor', '');
			
			wait = 60;
		}
		else{
			$('.binding_mobile #a_sent_ver').html('重新发送（'+ wait +"）");
			$('.binding_mobile #a_sent_ver').css('background', 'rgb(204, 204, 204)').css('color', 'rgb(51, 51, 51)').css('cursor', 'not-allowed');
		}
	}
}




