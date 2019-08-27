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
 * 平台账号设置Cdiscount账号设置js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		platform
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined') platform= new Object();

platform.CdiscountAccountsList = {	
	'initWidget': function() {
		// 绑定新的Cdiscount账号
		$("#new-Cdiscount-account-btn").unbind('click').click(function(){
			$.get( global.baseUrl+'platform/cdiscount-accounts/new',
			   function (data){
					bootbox.dialog({
						title : Translator.t("绑定新的Cdiscount账号"),
						className: "cdiscountAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Cdiscount-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("新建"),  
						        className: "btn-primary btn-Cdiscount-account-create",  
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
	
	'addCdiscountAccount' : function(){
		$.get( global.baseUrl+'platform/cdiscount-accounts/new',
			   function (data){
					bootbox.dialog({
						title : Translator.t("新建Cdiscount账号"),
						className: "cdiscountAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Cdiscount-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("新建"),  
						        className: "btn-primary btn-Cdiscount-account-create",  
					            callback: function () {  
					            	return false;
					            }  
					        }  
						},
					    message: data,
					});		
			}
		);
	},
	
	'openViewWindow': function(cdiscount_id){
		$.get( global.baseUrl+'platform/cdiscount-accounts/view-or-edit/?'+'cdiscount_id='+cdiscount_id+'&mode=view',
			function (data){
				bootbox.dialog({
					className: "cdiscountAccountInfo",
					title : Translator.t("查看Cdiscount账号信息"),
				    message: data,
				    buttons: {
						Cancel: {
					        label: Translator.t("返回"),  
					        className: "btn-default btn-cdiscount-account-return",  
					    }, 
					},
				});		
			}
		);
	},
	
	'openEditWindow': function(cdiscount_id){
		$.get( global.baseUrl+'platform/cdiscount-accounts/view-or-edit/?'+'cdiscount_id='+cdiscount_id+'&mode=edit',
			function (data){
				bootbox.dialog({
					title : Translator.t("编辑Cdiscount账号信息"),
					className: "cdiscountAccountInfo",
				    message: data,
				    buttons: {  
						Cancel: {  
					        label: Translator.t("返回"),  
					        className: "btn-default btn-Cdiscount-account-return",  
					    }, 
					    OK: {  
					        label: Translator.t("保存"),  
					        className: "btn-primary btn-Cdiscount-account-save",  
				            callback: function () {  
				            	return false;
				            }  
				        }  
					},
				});		
			}
		);
	},
	
	'delCdiscountAccount': function(cdiscount_id){
		bootbox.confirm({  
	        title : Translator.t('Confirm'),
			message : Translator.t('您确定要删除Cdiscount帐号与平台的绑定?'),  
	        callback : function(r) {  
	        	if (r){
					$.showLoading();
					$.post(
						global.baseUrl+'platform/cdiscount-accounts/delete',{cdiscount_id:cdiscount_id},
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
	
	}
};

function setCdiscountAccountSync(val,usr,sel){
	$.showLoading();
	$.ajax({
		type: "post",
		url: global.baseUrl+"platform/cdiscount-accounts/set-cdiscount-account-sync",
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
