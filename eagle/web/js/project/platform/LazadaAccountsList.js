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
 * 平台账号设置Lazada账号设置js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		platform
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined') platform= new Object();

platform.LazadaAccountsList = {	
	'initWidget': function() {	
		
	},
	
	'addLazadaAccount' : function(){
		$.ajax({
			url:global.baseUrl+'platform/lazada-accounts/new',
			data:{platform:'lazada'},
			type:'GET',
			async:false,//为了让后面的物流跟踪助手设置弹窗浮在最上面，这里设置为同步
			success:function (data){
				bootbox.dialog({
					title : Translator.t("新建Lazada账号"),
					className: "lazadaAccountInfo",
					buttons: {  
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default btn-lazada-account-return",  
						}, 
						OK: {  
							label: Translator.t("新建"),  
							className: "btn-primary btn-lazada-account-create",  
							callback: function () {  
								return false;
							}  
						}  
					},
					message: data,
				});		
			}
		});
		platform.LazadaAccountsList.setTrackerSyncBackDays('lazada');
	},
	
	'addLinioAccount' : function(){
		$.ajax({
			url:global.baseUrl+'platform/lazada-accounts/new',
			data:{platform:'linio'},
			type:'GET',
			async:false,//为了让后面的物流跟踪助手设置弹窗浮在最上面，这里设置为同步
			success:function (data){
				bootbox.dialog({
					title : Translator.t("新建Linio账号"),
					className: "lazadaAccountInfo",
					buttons: {  
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default btn-lazada-account-return",  
						}, 
						OK: {  
							label: Translator.t("新建"),  
							className: "btn-primary btn-lazada-account-create",  
							callback: function () {  
								return false;
							}  
						}  
					},
					message: data,
				});		
			}
		});
		platform.LazadaAccountsList.setTrackerSyncBackDays('linio');
	},
	
	'addJumiaAccount' : function(){
		$.ajax({
			url:global.baseUrl+'platform/lazada-accounts/new',
			data:{platform:'jumia'},
			type:'GET',
			async:false,//为了让后面的物流跟踪助手设置弹窗浮在最上面，这里设置为同步
			success:function (data){
				bootbox.dialog({
					title : Translator.t("新建Jumia账号"),
					className: "lazadaAccountInfo",
					buttons: {  
						Cancel: {  
							label: Translator.t("返回"),  
							className: "btn-default btn-lazada-account-return",  
						}, 
						OK: {  
							label: Translator.t("新建"),  
							className: "btn-primary btn-lazada-account-create",  
							callback: function () {  
								return false;
							}  
						}  
					},
					message: data,
				});		
			}
		});
		platform.LazadaAccountsList.setTrackerSyncBackDays('jumia');
	},
	
	'openViewWindow': function(lazada_uid,platform){
		$.get( global.baseUrl+'platform/lazada-accounts/view-or-edit/?'+'lazada_uid='+lazada_uid+'&platform='+platform+'&mode=view',
			function (data){
				bootbox.dialog({
					className: "lazadaAccountInfo",
					title : Translator.t("查看"+platform+"账号信息"),
				    message: data,
				    buttons: {  
						Cancel: {  
					        label: Translator.t("返回"),  
					        className: "btn-default btn-lazada-account-return",  
					    }, 
					},
				});		
			}
		);
	},
	
	'openEditWindow': function(lazada_uid,platform){
		$.get( global.baseUrl+'platform/lazada-accounts/view-or-edit/?'+'lazada_uid='+lazada_uid+'&platform='+platform+'&mode=edit',
			function (data){
				bootbox.dialog({
					title : Translator.t("编辑"+platform+"账号信息"),
					className: "lazadaAccountInfo",
				    message: data,
				    buttons: {  
						Cancel: {  
					        label: Translator.t("返回"),  
					        className: "btn-default btn-lazada-account-return",  
					    }, 
					    OK: {  
					        label: Translator.t("保存"),  
					        className: "btn-primary btn-lazada-account-save",  
				            callback: function () {  
				            	return false;
				            }  
				        }  
					},
				});		
			}
		);
	},
	
	'unbindLazadaAccount': function(lazada_uid,platform){
		bootbox.confirm({  
	        title : Translator.t('Confirm'),
			message : Translator.t('您确定要解绑'+platform+'帐号?'),  
	        callback : function(r) {  
	        	if (r){
					$.showLoading();
					$.post(
						global.baseUrl+'platform/lazada-accounts/unbind',{lazada_uid:lazada_uid},
						function (result){
							$.hideLoading();
							var retinfo = eval('(' + result + ')');
							if (retinfo["code"]=="fail")  {
								bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
								return false;
							}else{
								bootbox.alert({title:Translator.t('提示'),message:retinfo["message"],
									callback:function(){
										window.location.reload();
										$.showLoading();
									}
								});
							}
						}
					);
				}
	        },  
        });
	
	},
	'setLazadaAccountSync': function(platform, status , lazada_uid , platform_userid){
		$.showLoading();
		$.ajax({
			type: "post",
			url: global.baseUrl+"platform/lazada-accounts/set-lazada-account-sync",
			data: {"platform":platform, "status":status, "lazada_uid":lazada_uid, "platform_userid":platform_userid},
			cache: false,
			dataType:'json',
			success: function(data){
				if(data.code == 'ok'){
					$.hideLoading();
					bootbox.alert({ title:Translator.t('提示') , message:data.message , callback:function(){
						window.location.reload();
					}});
				}else{
					$.hideLoading();
					bootbox.alert({ title:Translator.t('提示') , message:data.message });
				}
			},
			error: function(){
				$.hideLoading();
				bootbox.alert({ title:Translator.t('提示') , message:Translator.t('后台传输出错!') });
			}
		});
	},
	
	'setTrackerSyncBackDays' : function(platform){
		//物流跟踪助手设置弹窗
		$.ajax({
			type: "post",
			dataType:"json",
			url: global.baseUrl+"tracking/tracking/check-platform-is-set-get-days-ago-order-track-no?platform="+platform,
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
									data: {days:$("#getHowManyDaysAgo").val(),platform:platform},
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
	},
	
	'lazadaAuthorizationUser' : function(lzd_uid){
		var left = Math.floor((Math.random()*$(window).width()/2)+1);
		var subfix = lzd_uid?"?lzd_uid="+lzd_uid:"";
		window.open (global.baseUrl+"platform/lazada-accounts/auth1/"+subfix,'newwindow', 'height=750,width=1136,top=100,left='+left+',toolbar=no,menubar=no,scrollbars=no, resizable=no,location=no, status=no');
	},
	
	// 拉取授权信息弹窗
	'openGetLazadaAuthInfoWindow' : function(){
		$.showLoading();
		$.ajax({
			type: "post",
			url: global.baseUrl+"platform/lazada-accounts/get-auth-info-window",
			cache: false,
			success: function(data){
				$.hideLoading();
				bootbox.dialog({
					title : Translator.t("获取授权信息"),
					className: "lazadaGetAuthInfo",
				    message: data,
				    buttons: {  
						Cancel: {  
					        label: Translator.t("返回"),  
					        className: "btn-default btn-lazada-get-auth-info-return",  
					    }, 
					    OK: {  
					        label: Translator.t("获取并保存"),  
					        className: "btn-primary btn-lazada-get-auth-info-save",  
				            callback: function () {  
				            	return false;
				            }  
				        }  
					},
				});		
			},
			error: function(){
				$.hideLoading();
				bootbox.alert({ title:Translator.t('提示') , message:Translator.t('后台传输出错!') });
			}
		});
	},
	
	
	
};
