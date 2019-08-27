if (typeof site === 'undefined')  site = new Object();
site.loginPage = {
	initWidget : function(){
		
//		$(document).ajaxStart(function () {
//			$.showLoading();
//		}).ajaxStop(function () {
//			$.hideLoading();
//		});
	},
	
	// 登录事件
	login : function(){	
		var _userNameInput = $('#loginForm #user_name');
		var _passwordInput = $('#loginForm #password');
		
		// 测试点击按钮出现报错效果
		if ($(_userNameInput).val() == '') {
			site.loginPage.showTip($(_userNameInput) , Translator.t('出错提示：账号不能为空'));
			return false;
		}else{
			site.loginPage.hideTip($(_userNameInput));
		}
		if ($(_passwordInput).val() == '') {
			site.loginPage.showTip($(_passwordInput) , Translator.t('出错提示：密码不能为空'));
			return false;
		}else{
			site.loginPage.hideTip($(_passwordInput));
		}
		
		site.loginPage.hideTip($('#loginForm #user_name, #loginForm #password'));
		var rememberme = $('input[name=rememberme_alert]').attr('checked') == 'checked' ? 1 : 0;
		$.showLoading();
		$.getJSON(
			global.baseUrl+'site/verify-user',
			{user_name: $(_userNameInput).val(), password: $(_passwordInput).val()},
			function(data){
				$.hideLoading();
				if(data.code === 200) {
					$('#loginForm').submit();
					return false;
				}
				var obj;
				if (data.code === 400) {
					obj = _userNameInput;
				} else if (data.code === 405) {
					obj = _passwordInput;
				}
				site.loginPage.showTip($(obj) , data.message);
			}
		);
		
		event.preventDefault();
	},

	showTip : function(obj,content){
		$(obj).focus();
		$(obj).parent().addClass('has-error');
		$(obj).qtip({
			content: {
		        text: content,
		    },
		    position : {
		    	at : 'middle right',
		    	my : 'middle left',
		    	container : $(obj).parents('.modal'),// 关闭modal时可以把qtip也remove掉
		    },
		    show: {
		        ready: true
		    },
			hide: {
		        event: false,
		    },
		    style: {
		        classes: 'qtip-bootstrap bg-danger text-danger'
		    }
		})
	},
	hideTip : function(obj){
		$(obj).parent().removeClass('has-error');
		$(obj).parent().addClass('has-success');
		$(obj).qtip('destroy');
	},
}
