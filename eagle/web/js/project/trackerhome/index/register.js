var guanwang = new Object();
guanwang.index = {
	//登陆事件
	login: function(){
		var rememberme = $('input[name=rememberMeAlert]').attr('checked') == 'checked' ? 1 : 0;
	    $.ajax({
	        type:'post',
	        url: global.erpUrl+'/site/login?callback=1',
	        data:  {
	        	'_csrf': $('input[name=_csrf]').val(),
	        	'user_name': $('input[name=username_login_alert]').val(),
                'password': $('input[name=password_login_alert]').val(),
                'eaglesite_login': $('input[name=eaglesite_login_alert]').val(),
                'rememberMe': rememberme
	        },
	        cache:false,
	        dataType:'json',
	        success: function(msg){  //调用域localhost的test.php（其实两个文件在相同目录下）
			    //lolo20150325
			    //var retinfo = eval('(' + msg + ')');
				var retinfo = msg ;
			   
	            if (retinfo["code"] != 200)  {
	               // alert('请检查邮箱和密码是否正确\n\r如果已注册成功但没有开通，请加入QQ群317561579，向管理员申请开通');
				    alert(retinfo["message"]);
	                return false;
	            }else{
	                //window.location.replace(global.hostInfo+'/index/erp');
	                //window.navigate(global.hostInfo+'/index/erp');
	                window.location.href = global.erpUrl;
	                //top.location = global.hostInfo+'/index/erp';
	                //self.location = global.hostInfo+'/index/erp';
	            }
	        }
	    });
	},
	//注册事件
	register: function(){
		var agreement = $('input[name=agreement]').attr('checked') == 'checked' ? 1 : 0;
		guanwang.index.showLoadingForRegister();
		$.getJSON(
			global.erpUrl+'site/register?callback=1',
			{
				'_csrf': $('input[name=_csrf]').val(),
			    'email': $('input[name=email]').val(),
                //'username': $('input[name=username]').val(),
			    'username': $('input[name=email]').val(),//去掉username，使用email
                'password': $('input[name=password]').val(),
                'repassword': $('input[name=repassword]').val(),
                'familyname': $('input[name=familyname]').val(),
                'address': $('input[name=address]').val(),
                'company': $('input[name=company]').val(),
                'cellphone': $('input[name=cellphone]').val(),
                'telephone': $('input[name=telephone]').val(),
                'qq': $('input[name=qq]').val(),
                'agreement': agreement,
                'registercode': $('input[name=registercode]').val(),
                'invitationcode': $('input[name=invitationcode]').val(),
                'authcode': $('input[name=authcode]').val()
			},
			function(msg){  //调用域localhost的test.php（其实两个文件在相同目录下）
				guanwang.index.hideLoadingForRegister()
                if(msg["code"] == 200){
					alert('用户【'+$('input[name=email]').val()+'】注册成功\n请加入QQ群317561579，向管理员申请开通');
					$(".aleTab>li:eq(0)").addClass("on");
			        $(".aleTab>li:eq(1)").removeClass("on");
			        $(".aleCon>.tab").hide();
			        $(".aleCon>.tab").eq($(".aleTab>li:eq(0)").index()).show();
                    //window.location.href = global.baseUrl+'index/erp';
                }else if (msg["code"] == 201){
                	window.location.href = global.erpUrl;
                }else{
                	alert(msg.message);
                }
			}
		)
	},
	//验证码
	authcode: function(){
        var timestamp = new Date().getTime();
        $('#guanwang-index-authcode').attr({'src': global.erpUrl+'/site/vericode?r='+timestamp});
	},
    //验证邮箱
    veriemail: function(){
        var count = 60;
        $("#guanwang-index-verify-email").attr("disabled", true);
        var countdown = setInterval(CountDown, 1000);
        function CountDown() {
            $("#guanwang-index-verify-email").html('等待'+count+'秒重新发送');
            if (count == 0) {
                $("#guanwang-index-verify-email").html("发送注册码").removeAttr("disabled");
                clearInterval(countdown);
            }
            count--;
        }
        $.getJSON(
            global.erpUrl+'/site/verify-email?callback=1',
            {'email':$('input[name=email]').val()},
            function(msg){  //调用域localhost的test.php（其实两个文件在相同目录下）
                if(msg["code"] == 200){
//                	alert(msg.message);
                }else{
                	 $("#guanwang-index-verify-email").html("发送注册码").removeAttr("disabled");
                	clearInterval(countdown);
                    alert(msg.message);
                }
			}
        )
    },
    //用户协议
    useragree: function(){
        $('#guanwang-index-reg-form').css({display:'none'});
        $('#guanwang-index-useragree').css({display:'block'});
    },
    //关闭用户协议
    closeUseragree: function(){
        $('#guanwang-index-useragree').css({display:'none'});
        $('#guanwang-index-reg-form').css({display:'block'});
    },
    
    // 忘记密码
    sendForgetPassMail : function(_submitBtn){
    	if(!guanwang.index.veriEmailFormat($('#site-request-password-reset-form input[name=forgetpass_email]').val())){
    		alert('邮箱格式不合法');
    		return ;
    	}
    	
		$(_submitBtn).html('邮件正在发送').attr("disabled",true);
		
		$.getJSON(
				global.baseUrl+'site/request-password-reset',
				{'email':$('#site-request-password-reset-form input[name=forgetpass_email]').val()},
				function(data){
					$(_submitBtn).html('发送').removeAttr("disabled");
					if(data.code == 200){
						$('.alertClose').click()
						alert(data.message);
					}else{
						alert(data.message);
					}
				}
			);
    },
    
    // 验证邮箱格式
    veriEmailFormat : function(txt){
    	return /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+/i.test(txt);
    },
    
	resetPassword : function(){
		if( $('input[name=password]').val().length < 6 ){
    		alert('密码长度不得少于六个字符');
    		return ;
		}
		
    	if ( $('input[name=repassword]').val().length < 6 ){
    		alert('确认密码长度不得少于六个字符');
    		return ;
    	}

    	if ( $('input[name=repassword]').val() != $('input[name=password]').val() ){
    		alert('确认密码与密码不一致');
    		return ;
    	}

		$.getJSON(
				$('#reset-password-form').attr('action') ,
				{ password : $('input[name=password]').val() , repassword : $('input[name=repassword]').val()},
				function(data){
					if(data.code == 200){
						alert(data.message);
						window.location.href = global.baseUrl;
					}else{
						alert(data.message);
					}
					
				}
			);
		
	},
	
	showLoadingForRegister : function(){
		var ajaxbg = $("#background,#progressBar");
		ajaxbg.show();
	},
	
	hideLoadingForRegister : function(){
		var ajaxbg = $("#background,#progressBar");
		ajaxbg.hide();
	},
}

$(function(){
    //AJAX 登陆
    $('#guanwang-index-login').click(function(){
        var rememberme = $('input[name=rememberMe]').attr('checked') == 'checked' ? 1 : 0;
        
	    $.ajax({
	        type:'post',
	        url: global.erpUrl+'/site/login?callback=1',
	        data:  {
	        	'_csrf': $('input[name=_csrf]').val(),
	            'user_name': $('input[name=username_login]').val(),
	            'password': $('input[name=password_login]').val(),
	            'eaglesite_login': $('input[name=eaglesite_login]').val(),
	            'rememberMe': rememberme
	        },
	        cache:false,
	        dataType:'json',
	        success: function(msg){  //调用域localhost的test.php（其实两个文件在相同目录下）
			    //lolo20150325
			    //var retinfo = eval('(' + msg + ')');
				var retinfo = msg ;
			   
	            if (retinfo["code"] != 200)  {
	               // alert('请检查邮箱和密码是否正确\n\r如果已注册成功但没有开通，请加入QQ群317561579，向管理员申请开通');
				    alert(retinfo["message"]);
	                return false;
	            }else{
	                //window.location.replace(global.hostInfo+'/index/erp');
	                //window.navigate(global.hostInfo+'/index/erp');
	                window.location.href = global.erpUrl;
	                //top.location = global.hostInfo+'/index/erp';
	                //self.location = global.hostInfo+'/index/erp';
	            }
	        }
	    });
    });
    
    
    
});