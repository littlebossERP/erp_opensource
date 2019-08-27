/**
 * 这里个js 一开始 打算是将下面几个view的html 隐藏放在main or menu layout里
 * 这样在其他页面都可以在menu触发这些弹窗进行登录，注册，找回密码 ，登出等操作
 * 
 * 但由于目前tracker自己有一套首页代码，可以代替上述功能，所以这个js 基本没用了，只剩下logout正在使用
 * 
 * 目前只要登出就会退回tracker首页，所以这里的registerView，loginView，forgetPassView用不到
 */

if (typeof site === 'undefined')  site = new Object();
site.index = {
	'registerView' : function(){
//		bootbox.dialog({
//    		className: 'max-modal-height',
//			title : Translator.t("注册小老板账号"),
//			buttons: {  
//				Cancel: {  
//                    label: Translator.t("取消"),  
//                    className: "btn-default",
//                }, 
//                OK: {  
//                    label: Translator.t("确认"),  
//                    className: "btn-primary",  
//                    callback: function () {
//                    	site.register.register();
//                    	return false;
//                    }  
//                }  
//			},
//		    message: $('#register-div').html(),
//		});	
		$("#register-modal").modal({'backdrop':'static'});
		$("#register-modal .modal-footer .btn-primary").click(function(){
			site.register.register();
		});
		return false;
	},
	
	
	'loginView' : function(){
//		bootbox.dialog({
//			className: 'max-modal-height',
//			title : Translator.t("欢迎登录小老板平台"),
//			buttons: {  
//				Cancel: {  
//                    label: Translator.t("取消"),  
//                    className: "btn-default",
//                }, 
//                OK: {  
//                    label: Translator.t("确认"),  
//                    className: "btn-primary",  
//                    callback: function () {
//                    	site.loginPage.login();
//                    	return false;
//                    } 
//                }  
//			},
//		    message: $('#login-div').html(),
//		});	
		$("#login-modal").modal({'backdrop':'static'});
		$("#login-modal .modal-footer .btn-primary").click(function(){
			site.loginPage.login();
		});
		return false;
	},
	
	
	'logout' : function(){
		$.ajax({
            type:'post',
            url: global.baseUrl+'site/logout',
            cache:false,
            complete:function(){
            	window.location.reload();
            },
        });
		return false;
	},
	
	'forgetPassView' : function(){
		$("#login-modal").modal('hide');
		$("#forgetpass-modal").modal({'backdrop':'static'});
		$("#login-modal .modal-footer .btn-primary").click(function(){
			site.loginPage.login();
		});
		
	},
	
}

function toggleSidebar(){
	var sidebarOffset = $("#sidebar").offset();
	if( -190 == sidebarOffset.left){
		
		$("#sidebar").animate({ left: 20 +'px'});
		$("#sidebar-controller").html("&lsaquo;");
		$(".col2-layout>.content-wrapper").animate({ marginLeft: 190 +'px'}).css("padding-left",20);
	}else{
		
		$("#sidebar").animate({ left: -190 +'px'});
		$("#sidebar-controller").html("&rsaquo;");
		$(".col2-layout>.content-wrapper").animate({ marginLeft: 0 +'px'}).css("padding-left",0);
	}
}


$(".toggleMenuL").click(function(){
	/*
	if ($(this).parent().children('ul').is(":hidden")==false){
		$(this).parent().children('ul').hide();
		
		if($(this).hasClass('glyphicon-menu-down'))
			$(this).removeClass('glyphicon-menu-down');
		$(this).addClass('glyphicon-menu-up');
	}else{
		$(this).parent().children('ul').show();
		
		if($(this).hasClass('glyphicon-menu-up'))
			$(this).removeClass('glyphicon-menu-up');
		$(this).addClass('glyphicon-menu-down');
	}
	*/
	
	if ($(this).parent().next('.sidebar-shrink-li').children('ul').is(":hidden")==false){
		$(this).parent().next('.sidebar-shrink-li').children('ul').hide();
		
		if($(this).hasClass('glyphicon-menu-down'))
			$(this).removeClass('glyphicon-menu-down');
		$(this).addClass('glyphicon-menu-up');
		
	}else{
		$(this).parent().next('.sidebar-shrink-li').children('ul').show();
		
		if($(this).hasClass('glyphicon-menu-up'))
			$(this).removeClass('glyphicon-menu-up');
		$(this).addClass('glyphicon-menu-down');
	}
	
});

$(".toggleMenuOutR").click(function(){
	if ($(this).parent().parent().next('ul').is(":hidden")==false){
		$(this).parent().parent().next('ul').hide();
		
		if($(this).hasClass('glyphicon-menu-down'))
			$(this).removeClass('glyphicon-menu-down');
		$(this).addClass('glyphicon-menu-up');
	}else{
		$(this).parent().parent().next('ul').show();
		
		if($(this).hasClass('glyphicon-menu-up'))
			$(this).removeClass('glyphicon-menu-up');
		$(this).addClass('glyphicon-menu-down');
	}
});



