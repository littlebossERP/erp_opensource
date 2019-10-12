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
 * 平台账号设置Wish账号设置js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		platform
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined') platform= new Object();

platform.WishAccountsList = {
	WishBaseUrl:"https://merchant.wish.com/oauth/authorize?client_id=56244353fc50a00faf804a43",
	initWidget: function() {	
		
		// 新建Wish账号
		$("#new-Wish-account-btn").unbind('click').click(function(){
			$.get( global.baseUrl+'platform/wish-accounts-v2/new',
			   function (data){
					bootbox.dialog({
						title : Translator.t("新建Wish账号"),
						className: "wishAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Wish-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("新建"),  
						        className: "btn-primary btn-Wish-account-create",  
					            callback: function () {  
					            	return false;
					            }  
					        }  
						},
					    message: data,
					});		
			});
		});
	},
	
	addWishAccount : function(){
		$.ajax({
			url:global.baseUrl+'platform/wish-accounts-v2/new',
			type:'GET',
			async:false,//为了让后面的物流跟踪助手设置弹窗浮在最上面，这里设置为同步
			success:function (data){
					bootbox.dialog({
						title : Translator.t("新建Wish账号"),
						className: "wishAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Wish-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("新建"),  
						        className: "btn-primary btn-Wish-account-create",  
					            callback: function () {  
									if ($('input[name=store_name]').parent().hasClass('has-error'))
										return false;
					            }  
					        }  
						},
					    message: data,
					});		
			}
		});
		//物流跟踪助手设置弹窗
		$.ajax({
			type: "post",
			dataType:"json",
			url: global.baseUrl+"tracking/tracking/check-platform-is-set-get-days-ago-order-track-no?platform=wish",
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
									data: {days:$("#getHowManyDaysAgo").val(),platform:'wish'},
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
	
	openViewWindow: function(wish_id){
		$.get( global.baseUrl+'platform/wish-accounts-v2/view-or-edit/?'+'wish_id='+wish_id+'&mode=view',
			function (data){
				bootbox.dialog({
					className: "wishAccountInfo",
					title : Translator.t("查看Wish账号信息"),
				    message: data,
				    buttons: {  
						Cancel: {  
					        label: Translator.t("返回"),  
					        className: "btn-default btn-Wish-account-return",  
					    }, 
					},
				});		
			}
		);
	},
	
	openEditWindow: function(wish_id){
		$.get( global.baseUrl+'platform/wish-accounts-v2/view-or-edit/?'+'wish_id='+wish_id+'&mode=edit',
			function (data){
				bootbox.dialog({
					title : Translator.t("编辑Wish账号信息"),
					className: "wishAccountInfo",
				    message: data,
				    buttons: {  
						Cancel: {  
					        label: Translator.t("返回"),  
					        className: "btn-default btn-Wish-account-return",  
					    }, 
					    OK: {  
					        label: Translator.t("保存"),  
					        className: "btn-primary btn-Wish-account-save",  
				            callback: function () {  
				            	return false;
				            }  
				        }  
					},
				});		
			}
		);
	},
	
	delWishAccount: function(wish_id){
		bootbox.confirm({  
	        title : Translator.t('Confirm'),
			message : Translator.t('您确定要删除Wish帐号?'),  
	        callback : function(r) {  
	        	if (r){
					$.showLoading();
					$.post(
						global.baseUrl+'platform/wish-accounts-v2/delete',{wish_id:wish_id},
						function (result){
							$.hideLoading();
							var retinfo = eval('(' + result + ')');
							if (retinfo["code"]=="fail")  {
								bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
								return false;
							}else{
								bootbox.alert({title:Translator.t('提示'),message:Translator.t('删除成功'),
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
	/**
	 * 重新授权
	 * lkh 2015-10-19
	 */
	rebindingWishAccount:function(site_id){
		var left = Math.floor((Math.random()*$(window).width()/2)+1);
		var WishBaseUrl=global.baseUrl+'platform/wish-accounts-v2/auth1?site_id='+site_id;
		window.open(WishBaseUrl,'newwindow', 'height=500,width=600,top=100,left='+left+',toolbar=no,menubar=no,scrollbars=no, resizable=no,location=no, status=no');
	},
	
	/**
	 * 授权
	 * lkh 2015-10-19
	 */
	authorizationUser:function(){
		var left = Math.floor((Math.random()*$(window).width()/2)+1);
		window.open(this.WishBaseUrl,'newwindow', 'height=500,width=600,top=100,left='+left+',toolbar=no,menubar=no,scrollbars=no, resizable=no,location=no, status=no');
	},
	
	accountAlias : function(site_id){
		var handle= $.openModal(global.baseUrl+"platform/wish-accounts-v2/setaliasbox",{site_id:site_id},'设置别名','post');  // 打开窗口命令
		handle.done(function($window){
	     // 窗口载入完毕事件
		 
		 $window.find("#btn_ok").on('click',function(){
			 btnObj = $(this);
			 btnObj.prop('disabled','disabled');
			  $.ajax({
					type: "POST",
					dataType: 'json',
					url:'/platform/wish-accounts-v2/save-alias', 
					data: $('#platform-wish-setalias-form').serialize(),
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
		
		
	},
	
};

function setWishAccountSync(val,usr,sel){
	$.showLoading();
	$.ajax({
		type: "post",
		url: global.baseUrl+"platform/wish-accounts-v2/set-wish-account-sync",
		data: {setval:val,setusr:usr,setitem:sel},
		cache: false,
		dataType:'json',
		success: function(data){
			if(data.success){
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
}

function wishGetOpenSourceAuth(){
	var handle= $.openModal(global.baseUrl+"platform/wish-accounts-v2/get-auth-info-window",{},'获取授权信息','post');  // 打开窗口命令
	handle.done(function($window){
     // 窗口载入完毕事件
		
	 $window.find("#btn_ok").on('click',function(){
		 btnObj = $(this);
		 btnObj.prop('disabled','disabled');
		  $.ajax({
				type: "POST",
				dataType: 'json',
				url:'/platform/wish-accounts-v2/auth4', 
				data: $('#platform-WishGetAuthInfo-form').serialize(),
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
