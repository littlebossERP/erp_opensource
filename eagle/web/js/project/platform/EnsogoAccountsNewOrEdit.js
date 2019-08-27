/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: lzhl
+----------------------------------------------------------------------
| Create Date: 2014-11-26
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 *查看/修改Ensogo账号的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		purchase
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined') platform= new Object();

platform.EnsogoAccountsNeworedit={
	    //mode can be new,view,edit
       'setting':{'mode':"","EnsogoData":""},
	   registerEnsogoAccount:function(){
		   if (! $('#platform-EnsogoAccounts-form').formValidation('form_validate')){
					//bootbox.alert(Translator.t('录入格式不正确!'));
					return false;
				}
            	$.showLoading();
				
				$.ajax({
					type: "POST",
					dataType: 'json',
					url:  global.baseUrl+'platform/ensogo-accounts/create',
					data: $("#platform-EnsogoAccounts-form").serialize(),
					success: function (data) {
						$.hideLoading();
						if (data.message == '') {
							data.message = '注册失败，请及时与客服联系';
						}
						if (data.success == false){
							//bootbox.alert(data.message);
							bootbox.alert({  
								buttons: {  
								   ok: {  
										label: '关闭',  
										className: 'btn-myStyle'  
									}  
								},  
								message: '<p style="font-size: 20px;">'+data.message+'.</p>',  
								callback: function() {  
									return true;
								},  
								title: "注册结果",  
							});  
						}else{
						    bootbox.alert({  
								buttons: {  
								   ok: {  
										label: '关闭',  
										className: 'btn-myStyle'  
									}  
								},  
								message: '<div style="text-align:center"><p style="font-size: 20px;">注册成功!</p><br>'+'<a href="'+data.platformUrl+'">点击查看您所注册的账号</a></div>',  
								callback: function() {  
									return true;
								},  
								title: "注册结果",  
							});  
						   //bootbox.alert('注册成功');  
						}
						return true;
					},
					error :function () {
						$.hideLoading();
						bootbox.alert({  
							buttons: {  
							   ok: {  
									label: '关闭',  
									className: 'btn-myStyle'  
								}  
							},  
							message: '<p style="font-size: 20px;">网络异常!注册失败，请及时与客服联系!</p>',  
							callback: function() {  
								return true;
							},  
							title: "注册结果",  
						});
						return false;
					}
				});
	   },
	   
	   initWidget: function() {
		   
		   
		   
			/**/
			$('#platform-EnsogoAccounts-form :text:not(#phone_tw_input)').each(function(){
				$(this).formValidation({validType:['trim','length[1,'+$(this).data('maxlength')+']'],tipPosition:'right',required:true});
			});
			
			$('#phone_type').change(function(){
			   $('#phone_cn').toggle();
			   $('#phone_tw').toggle();
			   $('#phone_tw_input').val('');
				$('#phone').val('');
			   /**/
			   if ($('#phone_cn').css('display') == 'block'){
				   $('#phone').formValidation({validType:['trim','length[1,'+$('#phone').data('maxlength')+']'],tipPosition:'right',required:true});
				   $('#phone_tw_input').formValidation({validType:['trim'],tipPosition:'right',required:false});
			   }else{
				   $('#phone_tw_input').formValidation({validType:['trim','length[1,'+$('#phone_tw_input').data('maxlength')+']'],tipPosition:'right',required:true});
				   $('#phone').formValidation({validType:['trim'],tipPosition:'right',required:false});
				   
			   }
			   
			   
		   });
			
	   	    var thisObject=platform.EnsogoAccountsNeworedit;
		    thisObject.initBtn();
		},
		initBtn:function(){
			
			$("#phonecodesend").click(function(){
				if ($('#phone_type').val()=='cn'){
					var phone = $.trim($("#phone").val());
					if(phone == ""){
						alert("请填写手机");
						return;
					}
					var reg = new RegExp("^1[0-9]{10}$");
					if(!reg.test(phone)){
						alert("手机格式不正确");
						return;
					}
				}else{
					var phone = $.trim($("#phone_tw").val());
					if(phone == ""){
						alert("请填写手机");
						return;
					}
					var reg = new RegExp("^\d{10}$");
					if(!reg.test(phone)){
						alert("手机格式不正确");
						return;
					}
				}
				
				var countdown = 120;
				var obj = $(this);
				//obj.button("loading");
				$('#phonecodesend').prop('disabled','disabled');
				$.ajax({
					type: "POST",
					dataType: 'json',
					url:'/platform/ensogo-accounts/phonecodesend', 
					data: {phone:phone},
					success: function (data) {
						if(data.success){
							platform.EnsogoAccountsNeworedit.settime(obj,countdown);
						}else if(data.success == false){
							bootbox.alert(data.msg);
							//obj.button("reset");
							$('#phonecodesend').prop('disabled','');
						}else{
							bootbox.alert("发送验证码失败，请联系管理员");
							//obj.button("reset");
							$('#phonecodesend').prop('disabled','');
						}			
						return true;
					},
					error: function(){
						bootbox.alert("Internal Error");
						return false;
					}
				});
				
			});

            $(".btn-Ensogo-account-create").click(function(){//新增Ensogo账号
				if (! $('#platform-EnsogoAccounts-form').formValidation('form_validate')){
					//bootbox.alert(Translator.t('录入格式不正确!'));
					return false;
				}
            	$.showLoading();
				
				$.ajax({
					type: "POST",
					dataType: 'json',
					url:  global.baseUrl+'platform/ensogo-accounts/create',
					data: $("#platform-EnsogoAccounts-form").serialize(),
					success: function (data) {
						$.hideLoading();
						if (data.success == false){
							bootbox.alert(data.message);
						}else{
						   //bootbox.alert("注册成功，请刷新页面");
						   bootbox.alert({  
								buttons: {  
								   ok: {  
										label: '确认',  
										className: 'btn-myStyle'  
									}  
								},  
								message: '注册成功，请刷新页面',  
								callback: function() {  
									$('.ensogoAccountInfo').modal('hide');
								},  
								
							});  
						}
						return true;
					},
					error :function () {
						$.hideLoading();
						return false;
					}
				});
				
				
					
		    });	
            $(".btn-Ensogo-account-save").click(function(){ // 编辑指定Ensogo的账号信息
				platform.EnsogoAccountsNeworedit.sumbitUpdateEnsogoInfo();
            });
		    
		},
		sumbitUpdateEnsogoInfo:function(){
			if (! $('#platform-EnsogoAccounts-form').formValidation('form_validate')){
				bootbox.alert(Translator.t('录入格式不正确!'));
				return false;
			}
			$.showLoading();
			$.post(
				   global.baseUrl+'platform/ensogo-accounts/update',$("#platform-EnsogoAccounts-form").serialize(),
				   function (data){
					   $.hideLoading();
					   var retinfo = eval('(' + data + ')');
					   if (retinfo["code"]=="fail")  {
						   bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
						   return false;
					   }else{
						   bootbox.alert({title:Translator.t('提示'),message:Translator.t('成功更新'),callback:function(){
									window.location.reload();
									$.showLoading();
								}
						   });
					   }
			});
		},
	
	settime:function(obj,countdown) { 
		//console.log(countdown);
		if (countdown == 0) { 
			$('#phonecodesend').prop('disabled','');
			obj.html("再发一次");
			return;
		} else { 
			obj.html("已发送("+countdown+")");
			countdown--; 
		} 
		setTimeout(function() { 
			platform.EnsogoAccountsNeworedit.settime(obj,countdown);
		},1000);
	} 
		
};
