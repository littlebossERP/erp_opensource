if (typeof site === 'undefined')  site = new Object();
site.requestPassReset = {
	initWidget : function(){
		
	},
	
	sentMail : function(){
		if(!site.requestPassReset.veriEmailFormat($('#site-request-password-reset-form input[name=email]').val())){
    		$('#site-request-password-reset-form input[name=email]').parent().addClass('has-error');//has-success
    		bootbox.alert({message:Translator.t('邮箱格式不合法'),});
    		return ;
    	}else{
    		$('#site-request-password-reset-form input[name=email]').parent().removeClass('has-error');
    		$('#site-request-password-reset-form input[name=email]').parent().addClass('has-success');
    	}
		var _submitBtn = event.target;
		$(_submitBtn).html(Translator.t('邮件正在发送')).attr("disabled",true);
		
		$.showLoading();
		$.getJSON(
				global.baseUrl+'site/request-password-reset?'+$('#site-request-password-reset-form').serialize(),
				'',
				function(data){
					$.hideLoading();
					$(_submitBtn).html(Translator.t('发送')).removeAttr("disabled");
					if(data.code == 200){
						bootbox.alert({message:data.message,callback:function(){
							window.location.href = global.baseUrl;
						    $.showLoading();
						}});
					}else{
						bootbox.alert({message:data.message});
						$('#site-request-password-reset-form input[name=email]').parent().addClass('has-error');
					}
				}
			);
	},
	
	// 验证邮箱格式
    veriEmailFormat : function(txt){
    	return /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+/i.test(txt);
    }
}
