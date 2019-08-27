/**
 * 修改敦煌账号
 * dzt 2015-03-03
 */
function dhgateEditUser(aliexpress_uid){
	$.showLoading();
	$.get( global.baseUrl+'platform/dhgate-accounts/view?'+'dhgate_uid='+aliexpress_uid,
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
function dhgateSaveUser(){
	$.showLoading();
	$("#id_sellerloginid").removeAttr("disabled");
	$.post(
		global.baseUrl+'platform/dhgate-accounts/edit',$("#fm").serialize(),
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
function dhgateSetSync(dhgate_uid , sellerloginid , is_active){
	$.showLoading();
	$.post(
		global.baseUrl+'platform/dhgate-accounts/edit',{'dhgate_uid':dhgate_uid,'sellerloginid':sellerloginid,'is_active':is_active},
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
 * dzt 2015-06-26
 */
function dhgateUnbindUser(aliexpress_uid , sellerloginid){
	 bootbox.confirm({  
	        title : Translator.t('账号解绑确认'),
			message : '是否确定解绑账号'+sellerloginid+'?',  
	        callback : function(r) {  
				if (r) {
					$.showLoading();
					$.post( global.baseUrl+'platform/dhgate-accounts/unbind', {'dhgate_uid':aliexpress_uid,'sellerloginid':sellerloginid} , function (data){
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
	        },  
     });
	
}
/**
 * 授权
 * dzt 2015-03-03
 */
function dhgateAuthorizationUser(){
	var left = Math.floor((Math.random()*$(window).width()/2)+1);
	window.open (global.baseUrl+"platform/dhgate-accounts/auth1/",'newwindow', 'height=500,width=600,top=100,left='+left+',toolbar=no,menubar=no,scrollbars=no, resizable=no,location=no, status=no');
	//物流跟踪助手设置弹窗
	$.ajax({
		type: "post",
		dataType:"json",
		url: global.baseUrl+"tracking/tracking/check-platform-is-set-get-days-ago-order-track-no?platform=dhgate",
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
								data: {days:$("#getHowManyDaysAgo").val(),platform:'dhgate'},
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
function dhgateMyreload(status , msg){
	bootbox.alert({title:status,message:msg,callback:function(){
		window.location.reload();
		$.showLoading();
		}
	});
}