if (typeof site === 'undefined')  site = new Object();
site.register = {
	
	// check initial apps	
	initWidget : function(){
		$(document).on('click','#checkAll',function(){
			site.register.checkAll(this);
		});
		
//		$(document).ajaxStart(function () {
//			$.showLoading();
//		}).ajaxStop(function () {
//			$.hideLoading();
//		});
	},
	
	checkAll : function(checkAll){
		if($(checkAll).prop('checked')){
			$(':checkbox.choose-app').prop('checked',true); 
		}else{
			$(':checkbox.choose-app').prop('checked',false); 
		}
	}, 
	
	openUserAgreementBox : function(){
		bootbox.dialog({
			className : "userArgreementBox",
			title : $("#user-agreement-title").html(),
		    buttons: {
		        ok: {
		            label: Translator.t("同意"),
		            callback: function () {
		            	$(':checkbox[name="agreement"]').prop('checked',true); 
		            	return true;
                    }  
		        },
		    },
		    message: $("#user-agreement-content").html(),
		});		
	},
	
	//注册事件
	register: function(){
		
		var agreement =$('#site-index-reg-form input[name=agreement]').prop('checked') == true ? 1 : 0;
		var applist = Array();
		$(':checkbox.choose-app').each(function(){
			if($(this).prop('checked')){
				var appKey = $(this).attr('name');
				applist.push(appKey.substring(appKey.indexOf('app_') + 'app_'.length - 1 + 1));
			}
		});
		
		$.getJSON(
			global.baseUrl+'site/register',
			{
				'applist':applist.join(),
			    'email': $('#site-index-reg-form input[name=email]').val(),
                'username': $('#site-index-reg-form input[name=username]').val(),
                'password': $('#site-index-reg-form input[name=password]').val(),
                'repassword': $('#site-index-reg-form input[name=repassword]').val(),
                'familyname': $('#site-index-reg-form input[name=familyname]').val(),
                'address': $('#site-index-reg-form input[name=address]').val(),
                'company': $('#site-index-reg-form input[name=company]').val(),
                'cellphone': $('#site-index-reg-form input[name=cellphone]').val(),
                'telephone': $('#site-index-reg-form input[name=telephone]').val(),
                'qq': $('#site-index-reg-form input[name=qq]').val(),
                'agreement': agreement,
                'registercode': $('#site-index-reg-form input[name=registercode]').val(),
                'invitationcode': $('#site-index-reg-form input[name=invitationcode]').val(),
                'authcode': $('#site-index-reg-form input[name=authcode]').val()
			},
			function(data){  
                if(data.code == 200){
                	bootbox.alert({title:"",message:Translator.t('账号【')+$('input[name=email]').val()+Translator.t('】注册成功\n请加入QQ群317561579，向管理员申请开通'),callback:function(){
                		window.location.href = global.baseUrl+'site/login';
					    $.showLoading();
				   	}});
                }else{
                    bootbox.alert({title:data.code,message:data.message,});
                }
			}
		)
	},
	
	//验证码
	authcode: function(){
        var timestamp = new Date().getTime();
        $('#site-index-authcode').attr({'src': global.baseUrl+'site/vericode?r='+timestamp});
	},
	
    // 发送验证码到邮箱
    veriemail: function(){
    	if(!site.register.veriEmailFormat($('input[name=email]').val())){
    		$('input[name=email]').parent().addClass('has-error');//has-success
//    		$('input[name=email]').next().html('邮箱格式不合法');
    		bootbox.alert({message:Translator.t('邮箱格式不合法'),});
    		return ;
    	}else{
    		$('input[name=email]').parent().removeClass('has-error');
    		$('input[name=email]').parent().addClass('has-success');
//    		$('input[name=email]').next().html('');
    	}
    		
        var count = 60;
        $("#site-index-verify-email").attr("disabled", true);
        var countdown = setInterval(CountDown, 1000);
        function CountDown() {
            $("#site-index-verify-email").html(Translator.t('等待')+count+Translator.t('秒重新发送'));
            if (count == 0) {
                $("#site-index-verify-email").html(Translator.t("发送注册码")).removeAttr("disabled");
                clearInterval(countdown);
            }
            count--;
        }
        $.getJSON(
    		global.baseUrl+'site/verify-email',{'email':$('input[name=email]').val()},function(data){
    			if(data.code != 200){
    				$('input[name=email]').parent().removeClass('has-success');
    				$('input[name=email]').parent().addClass('has-error');
    				$("#site-index-verify-email").html(Translator.t("发送注册码")).removeAttr("disabled");
    				clearInterval(countdown);
    				bootbox.alert({title:data.code,message:data.message,});
    			} else {
    				bootbox.alert({title:data.code,message:data.message,});
    			}
    		}
        )
    },
    
    // 验证邮箱格式
    veriEmailFormat : function(txt){
    	return /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+/i.test(txt);
//    	return /^[A-Za-zd]+([-_.][A-Za-zd]+)*@([A-Za-zd]+[-.])+[A-Za-zd]{2,5}$/i.test(txt);
    }
}

