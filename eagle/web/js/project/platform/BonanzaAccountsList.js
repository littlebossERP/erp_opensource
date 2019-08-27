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
 * 平台账号设置Bonanza账号设置js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		platform
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined') platform= new Object();

platform.BonanzaAccountsList = {	
	'initWidget': function() {
		// 绑定新的Bonanza账号
		$("#new-Bonanza-account-btn").unbind('click').click(function(){
			$.get( global.baseUrl+'platform/bonanza-accounts/new',
			   function (data){
					bootbox.dialog({
						title : Translator.t("绑定新的Bonanza账号"),
						className: "bonanzaAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Bonanza-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("新建"),  
						        className: "btn-primary btn-Bonanza-account-create",  
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
	
	'addBonanzaAccount' : function(){
		$.showLoading();
		$.get( global.baseUrl+'platform/bonanza-accounts/new',
			   function (data){
					$.hideLoading();
					bootbox.dialog({
						title : Translator.t("新建Bonanza账号"),
						className: "bonanzaAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Bonanza-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("绑定成功，确定并新建"),  
						        className: "btn-primary btn-Bonanza-account-create btn-disabled",  
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
	
	'openViewWindow': function(bonanza_id){
		$.get( global.baseUrl+'platform/bonanza-accounts/view-or-edit/?'+'bonanza_id='+bonanza_id+'&mode=view',
			function (data){
				bootbox.dialog({
					className: "bonanzaAccountInfo",
					title : Translator.t("查看Bonanza账号信息"),
				    message: data,
				    buttons: {
						Cancel: {
					        label: Translator.t("返回"),  
					        className: "btn-default btn-bonanza-account-return",  
					    }, 
					},
				});		
			}
		);
	},
	
	'openEditWindow': function(bonanza_id){
		$.showLoading();
		$.get( global.baseUrl+'platform/bonanza-accounts/view-or-edit/?'+'bonanza_id='+bonanza_id+'&mode=edit',
			function (data){
				$.hideLoading();
				bootbox.dialog({
					title : Translator.t("编辑Bonanza账号信息"),
					className: "bonanzaAccountInfo",
				    message: data,
				    buttons: {  
						Cancel: {  
					        label: Translator.t("返回"),  
					        className: "btn-default btn-Bonanza-account-return",  
					    }, 
					    OK: {  
					        label: Translator.t("保存"),  
					        className: "btn-primary btn-Bonanza-account-save",  
				            callback: function () {  
				            	return false;
				            }  
				        }  
					},
				});		
			}
		);
	},
	
	'delBonanzaAccount': function(bonanza_id){
		bootbox.confirm({  
	        title : Translator.t('Confirm'),
			message : Translator.t('您确定要删除Bonanza帐号与平台的绑定?'),  
	        callback : function(r) {  
	        	if (r){
					$.showLoading();
					$.post(
						global.baseUrl+'platform/bonanza-accounts/delete',{bonanza_id:bonanza_id},
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

function setBonanzaAccountSync(val,usr,sel){
	$.showLoading();
	$.ajax({
		type: "post",
		url: global.baseUrl+"platform/bonanza-accounts/set-bonanza-account-sync",
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
