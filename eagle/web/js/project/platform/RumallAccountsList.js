/*+----------------------------------------------------------------------
  | 小老板
  +----------------------------------------------------------------------
  | Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
  +----------------------------------------------------------------------
  | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
  +----------------------------------------------------------------------
  | Author: lwj
  +----------------------------------------------------------------------
  | Create Date: 2016-04-18
  +----------------------------------------------------------------------
  */

/**
  +------------------------------------------------------------------------------
 * 平台账号设置Rumall账号设置js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		platform
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined') platform= new Object();

platform.RumallAccountsList = {	
	'initWidget': function() {
		// 绑定新的Rumall账号
		$("#new-Rumall-account-btn").unbind('click').click(function(){
			$.get( global.baseUrl+'platform/rumall-accounts/new',
			   function (data){
					bootbox.dialog({
						title : Translator.t("绑定新的Rumall账号"),
						className: "rumallAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Rumall-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("新建"),  
						        className: "btn-primary btn-Rumall-account-create",  
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
	
	'addRumallAccount' : function(){
		$.showLoading();
		$.get( global.baseUrl+'platform/rumall-accounts/new',
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						title : Translator.t("新建Rumall账号"),
						className: "rumallAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Rumall-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("新建"),  
						        className: "btn-primary btn-Rumall-account-create btn-disabled",  
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
	
	'openViewWindow': function(rumall_id){
		$.get( global.baseUrl+'platform/rumall-accounts/view-or-edit/?'+'rumall_id='+rumall_id+'&mode=view',
			function (data){
				bootbox.dialog({
					className: "rumallAccountInfo",
					title : Translator.t("查看Rumall账号信息"),
				    message: data,
				    buttons: {
						Cancel: {
					        label: Translator.t("返回"),  
					        className: "btn-default btn-rumall-account-return",  
					    }, 
					},
				});		
			}
		);
	},
	
	'openEditWindow': function(rumall_id){
		$.showLoading();
		$.get( global.baseUrl+'platform/rumall-accounts/view-or-edit/?'+'rumall_id='+rumall_id+'&mode=edit',
			function (data){
				$.hideLoading();
				bootbox.dialog({
					title : Translator.t("编辑Rumall账号信息"),
					className: "rumallAccountInfo",
				    message: data,
				    buttons: {  
						Cancel: {  
					        label: Translator.t("返回"),  
					        className: "btn-default btn-Rumall-account-return",  
					    }, 
					    OK: {  
					        label: Translator.t("保存"),  
					        className: "btn-primary btn-Rumall-account-save",  
				            callback: function () {  
				            	return false;
				            }  
				        }  
					},
				});		
			}
		);
	},
	
	'delRumallAccount': function(rumall_id){
		bootbox.confirm({  
	        title : Translator.t('Confirm'),
			message : Translator.t('您确定要删除Rumall帐号与平台的绑定?'),  
	        callback : function(r) {  
	        	if (r){
					$.showLoading();
					$.post(
						global.baseUrl+'platform/rumall-accounts/delete',{rumall_id:rumall_id},
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

function setRumallAccountSync(val,usr,sel){
	$.showLoading();
	$.ajax({
		type: "post",
		url: global.baseUrl+"platform/rumall-accounts/set-rumall-account-sync",
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
