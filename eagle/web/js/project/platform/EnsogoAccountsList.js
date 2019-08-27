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
 * 平台账号设置Ensogo账号设置js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		platform
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined') platform= new Object();

platform.EnsogoAccountsList = {
	EnsogoBaseUrl:"https://merchant.Ensogo.com/oauth/authorize?client_id=56244353fc50a00faf804a43",
	initWidget: function() {	
		
		// 新建Ensogo账号
		/*
		$("#new-Ensogo-account-btn").unbind('click').click(function(){
			$.get( global.baseUrl+'platform/ensogo-accounts/new',
			   function (data){
					bootbox.dialog({
						title : Translator.t("注册Ensogo账号"),
						className: "ensogoAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Ensogo-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("注册"),  
						        className: "btn-primary btn-Ensogo-account-create",  
					            callback: function () {  
					            	return false;
					            }  
					        }  
						},
					    message: data,
					});		
			});
		});
		*/
	},
	
	addEnsogoAccount : function(){
		$.get( global.baseUrl+'platform/ensogo-accounts/new',
			   function (data){
					bootbox.dialog({
						title : Translator.t("注册Ensogo账号"),
						className: "ensogoAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Ensogo-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("注册"),  
						        className: "btn-primary btn-Ensogo-account-create",  
					            callback: function () {  
									if ($('input[name=store_name]').parent().hasClass('has-error'))
										return false;
									
									return false;
					            }  
					        }  
						},
					    message: data,
					});		
			}
		);
	},
	
	openViewWindow: function(ensogo_id){
		$.get( global.baseUrl+'platform/ensogo-accounts/view-or-edit/?'+'ensogo_id='+ensogo_id+'&mode=view',
			function (data){
				bootbox.dialog({
					className: "ensogoAccountInfo",
					title : Translator.t("查看Ensogo账号信息"),
				    message: data,
				    buttons: {  
						Cancel: {  
					        label: Translator.t("返回"),  
					        className: "btn-default btn-Ensogo-account-return",  
					    }, 
					},
				});		
			}
		);
	},
	
	openEditWindow: function(ensogo_id){
		$.get( global.baseUrl+'platform/ensogo-accounts/view-or-edit/?'+'ensogo_id='+ensogo_id+'&mode=edit',
			function (data){
				bootbox.dialog({
					title : Translator.t("编辑Ensogo账号信息"),
					className: "ensogoAccountInfo",
				    message: data,
				    buttons: {  
						Cancel: {  
					        label: Translator.t("返回"),  
					        className: "btn-default btn-Ensogo-account-return",  
					    }, 
					    OK: {  
					        label: Translator.t("保存"),  
					        className: "btn-primary btn-Ensogo-account-save",  
				            callback: function () {  
				            	return false;
				            }  
				        }  
					},
				});		
			}
		);
	},
	
	delEnsogoAccount: function(ensogo_id){
		bootbox.confirm({  
	        title : Translator.t('Confirm'),
			message : Translator.t('您确定要删除Ensogo帐号?'),  
	        callback : function(r) {  
	        	if (r){
					$.showLoading();
					$.post(
						global.baseUrl+'platform/ensogo-accounts/delete',{ensogo_id:ensogo_id},
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
	rebindingEnsogoAccount:function(site_id){
		var left = Math.floor((Math.random()*$(window).width()/2)+1);
		var EnsogoBaseUrl=global.baseUrl+'platform/ensogo-accounts/auth1?site_id='+site_id;
		window.open(EnsogoBaseUrl,'newwindow', 'height=500,width=600,top=100,left='+left+',toolbar=no,menubar=no,scrollbars=no, resizable=no,location=no, status=no');
	},
	
	/**
	 * 授权
	 * lkh 2015-10-19
	 */
	authorizationUser:function(){
		var left = Math.floor((Math.random()*$(window).width()/2)+1);
		window.open(this.EnsogoBaseUrl,'newwindow', 'height=500,width=600,top=100,left='+left+',toolbar=no,menubar=no,scrollbars=no, resizable=no,location=no, status=no');
	}
};

function setEnsogoAccountSync(val,usr,sel){
	$.showLoading();
	$.ajax({
		type: "post",
		url: global.baseUrl+"platform/ensogo-accounts/set-ensogo-account-sync",
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