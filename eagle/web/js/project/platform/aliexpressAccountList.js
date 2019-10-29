/**
 * 修改速卖通账号
 * dzt 2015-03-03
 */
function editUser(aliexpress_uid){
	$.showLoading();
	$.get( global.baseUrl+'platform/aliexpress-accounts/view?'+'aliexpress_uid='+aliexpress_uid,
	   function (data){
			$.hideLoading();
			currentDialog = bootbox.dialog({
				title : Translator.t("修改"),
			    message: data,
			});		
	});
}

/**
 * 保存
 * dzt 2015-03-03
 */
function saveUser(){
	$.showLoading();
	$("#id_sellerloginid").removeAttr("disabled");
	$.post(
		global.baseUrl+'platform/aliexpress-accounts/edit',$("#fm").serialize(),
		function (data){
			$.hideLoading();
			var retinfo = eval('(' + data + ')');
			if (retinfo["code"]=="fail")  {
				bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
				return false;
			}else{
				if($(currentDialog).length > 0)
					$(currentDialog).modal('hide');
				
				bootbox.alert({title:Translator.t('提示'),message:Translator.t('成功更新'),callback:function(){
					window.location.reload();
					$.showLoading();
				}});
			}
		}
	);
}

/**
 * 同步
 * dzt 2015-03-25 for tracking 界面
 */
function setSync(aliexpress_uid , sellerloginid , is_active){
	$.showLoading();
	$.post(
		global.baseUrl+'platform/aliexpress-accounts/edit',{'aliexpress_uid':aliexpress_uid,'sellerloginid':sellerloginid,'is_active':is_active},
		function (data){
			$.hideLoading();
			var retinfo = eval('(' + data + ')');
			if (retinfo["code"]=="fail")  {
				bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
				return false;
			}else{
				bootbox.alert({title:Translator.t('提示'),message:retinfo["message"],callback:function(){
					window.location.reload();
					$.showLoading();
				}});
			}
		}
	);
}

/**
 * 解除绑定
 * dzt 2015-03-25 for tracking 界面
 */
function delUser(aliexpress_uid , sellerloginid){
	bootbox.confirm({
		title : Translator.t('Confirm'),
		message : Translator.t('您确定要删除AliExpress帐号?'),  
	    callback : function(r) {
			if (r){
				$.showLoading();
				$.post( global.baseUrl+'platform/aliexpress-accounts/delete', {'aliexpress_uid':aliexpress_uid,'sellerloginid':sellerloginid} , function (data){
					$.hideLoading();
					var retinfo = eval('(' + data + ')');
					if (retinfo["code"]=="fail")  {
						bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
						return false;
					}else{
						bootbox.alert({title:Translator.t('提示'),message:retinfo["message"],callback:function(){
							window.location.reload();
							$.showLoading();
						}});
					}
				});
			}
		}
	});
}
/**
 * 授权
 * dzt 2015-03-03
 */
function authorizationUser(auth_type, is_refresh){
	if(auth_type == 1){
		var url = global.baseUrl+"platform/aliexpress-accounts-v2/auth1/";
	}
	else{
		var url = global.baseUrl+"platform/aliexpress-accounts/auth1/";
	}
	 
	
	var left = Math.floor((Math.random()*$(window).width()/2)+1);
	window.open (url,'newwindow', 'height=750,width=1136,top=100,left='+left+',toolbar=no,menubar=no,scrollbars=no, resizable=no,location=no, status=no');

 
	 
	//物流跟踪助手设置弹窗
	$.ajax({
		type: "post",
		dataType:"json",
		url: global.baseUrl+"tracking/tracking/check-platform-is-set-get-days-ago-order-track-no?platform=aliexpress",
		cache:false,
		success: function(data){
			if(!data.show || true)
				return false;
			bootbox.dialog({
				title : Translator.t("设置物流跟踪助手新绑定账号同步天数"),
				message: data.html,
				buttons:{
					Ok: {
						label: Translator.t("设置"),
						className: "btn-success btn",
						callback: function () {
							$.showLoading();
							$.ajax({
								type: "POST",
								url:'/tracking/tracking/set-get-days-ago-order-track-no',
								data: {days:$("#getHowManyDaysAgo").val(),platform:'aliexpress'},
								success: function (result) {
									$.hideLoading();
									return true;
								},
								error : function(){
									$.hideLoading();
									return false;
								}
							});
						}
					}
				}
			});
		}
	});
}

/**
 * 刷新
 * dzt 2015-03-03
 */
function myreload(status , msg){
	bootbox.alert({title:status,message:msg,callback:function(){
		window.location.reload();
		$.showLoading();
		}
	});
}

function setAliexpressAccountAlias(uid,sellerid){
	var handle= $.openModal(global.baseUrl+"platform/aliexpress-accounts/setaliasbox",{uid:uid,sellerid:sellerid},'设置别名','post');  // 打开窗口命令
	handle.done(function($window){
     // 窗口载入完毕事件
     //$window.find("input[type=date]").datepicker();    // 渲染日历选择框
	 
	 $window.find("#btn_ok").on('click',function(){
		 btnObj = $(this);
		 btnObj.prop('disabled','disabled');
		  $.ajax({
				type: "POST",
				dataType: 'json',
				url:'/platform/aliexpress-accounts/save-alias', 
				data: $('#platform-aliexpress-setalias-form').serialize(),
				success: function (result) {
					if (result.success ){
						$.alert(Translator.t('操作成功'));
						$window.close(); 
						window.location.reload();
					}else{
						$.alert(result.message);
						btnObj.prop('disabled','');
					}
				}
				 
			 });
		 
     })
     $window.find("#btn_cancel").on('click',function(){
            $window.close();       // 关闭当前模态框
     })
});
}

function getOpenSourceAuth(){
	var handle= $.openModal(global.baseUrl+"platform/aliexpress-accounts/get-auth-info-window",{},'获取授权信息','post');  // 打开窗口命令
	handle.done(function($window){
     // 窗口载入完毕事件
		
	 $window.find("#btn_ok").on('click',function(){
		 btnObj = $(this);
		 btnObj.prop('disabled','disabled');
		  $.ajax({
				type: "POST",
				dataType: 'json',
				url:'/platform/aliexpress-accounts-v2/auth4', 
				data: $('#platform-AliexpressGetAuthInfo-form').serialize(),
				success: function (result) {
					if (result.code == 200){
						$.alert(Translator.t('操作成功'));
						$window.close(); 
						window.location.reload();
					}else{
						$.alert(result.message);
						btnObj.prop('disabled','');
					}
				}
				 
			 });
		 
     })
     $window.find("#btn_cancel").on('click',function(){
            $window.close();       // 关闭当前模态框
     })
});
}

