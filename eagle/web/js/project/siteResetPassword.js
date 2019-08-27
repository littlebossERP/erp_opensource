if (typeof site === 'undefined')  site = new Object();
site.resetPassword = {
	setting : {initError:'',},
	initWidget : function(){
		if(site.resetPassword.setting.initError){
			var error = eval('('+site.resetPassword.setting.initError+')');
			bootbox.alert({message:error.message});
		}
	},
	
	save : function(){
		
		if( $('input[name=password]').val().length < 6 ){
    		$('input[name=password]').parent().addClass('has-error');
    		bootbox.alert({message:Translator.t('密码长度不得少于六个字符')});
    		return ;
    	} else {
    		$('input[name=password]').parent().removeClass('has-error');
    		$('input[name=password]').parent().addClass('has-success');
    	}
    	if ( $('input[name=repassword]').val().length < 6 ){
    		$('input[name=repassword]').parent().addClass('has-error');
    		bootbox.alert({message:Translator.t('确认密码长度不得少于六个字符')});
    		return ;
    	} else {
    		$('input[name=repassword]').parent().removeClass('has-error');
    		$('input[name=repassword]').parent().addClass('has-success');
    	}
    	if ( $('input[name=repassword]').val() != $('input[name=password]').val() ){
    		$('input[name=repassword]').parent().addClass('has-error');
    		bootbox.alert({message:Translator.t('确认密码与密码不一致')});
    		return ;
    	} else {
    		$('input[name=repassword]').parent().removeClass('has-error');
    		$('input[name=repassword]').parent().addClass('has-success');
    	}
		
		$.showLoading();
		$.getJSON(
				$('#reset-password-form').attr('action') ,
				{ password : $('input[name=password]').val() , repassword : $('input[name=repassword]').val()},
				function(data){
					$.hideLoading();
					if(data.code == 200){
						bootbox.alert({message:data.message,callback:function(){
							window.location.href = global.baseUrl;
						    $.showLoading();
						}});
					}else{
						bootbox.alert({message:data.message});
					}
					
				}
			);
	},
	
	reset : function(){
		$('input[name=repassword],input[name=password]').parent().removeClass('has-error');
		$('input[name=repassword],input[name=password]').parent().removeClass('has-success');
	}
	
}
