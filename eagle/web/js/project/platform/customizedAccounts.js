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
 * 自定义账号绑定设置js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		platform
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined') platform= new Object();

platform.CustomizedAccountsList = {	
	'initWidget': function() {
		// 绑定新的Paypal账号
		$("#new-Customized-account-btn").unbind('click').click(function(){
			$.get( global.baseUrl+'platform/customized-accounts/new',
			   function (data){
					bootbox.dialog({
						title : Translator.t("绑定新的自定义账号"),
						className: "CustomizedAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Customized-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("新建"),  
						        className: "btn-primary btn-Customized-account-create",  
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
	
	'addCustomizedAccount' : function(){
		$.get( global.baseUrl+'platform/customized-accounts/new',
			   function (data){
					bootbox.dialog({
						title : Translator.t("自定义店铺"),
						className: "CustomizedAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Customized-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("新建"),  
						        className: "btn-primary btn-Customized-account-create",  
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
	
	'editCustomizedAccount' : function(site_id){
		$.get( global.baseUrl+'platform/customized-accounts/edit?site_id='+site_id,
			   function (data){
					bootbox.dialog({
						title : Translator.t("自定义店铺"),
						className: "CustomizedAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Customized-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("保存"),  
						        className: "btn-primary btn-Customized-account-save",  
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
	
	'delCustomizedAccount': function(site_id){
		bootbox.confirm({  
	        title : Translator.t('Confirm'),
			message : Translator.t('您确定要删除自定义店铺帐号与平台的绑定?'),  
	        callback : function(r) {  
	        	if (r){
					$.showLoading();
					$.post(
						global.baseUrl+'platform/customized-accounts/delete',{site_id:site_id},
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
	
	'switchActive' : function (site_id,is_active){
		if(is_active==1)
			var ov_ch = '开启使用';
		else
			var ov_ch = '停止使用';
		bootbox.confirm({  
	        title : Translator.t('Confirm'),
			message : Translator.t('您确定要'+ov_ch+'该自定义店铺账号?'),  
	        callback : function(r) {  
	        	if (r){
					$.showLoading();
					$.post(
						global.baseUrl+'platform/customized-accounts/switch-active',{site_id:site_id,is_active:is_active},
						function (result){
							$.hideLoading();
							var retinfo = eval('(' + result + ')');
							if (retinfo["code"]=="fail")  {
								bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
								return false;
							}else{
								bootbox.alert({title:Translator.t('提示'),message:Translator.t('操作成功'),
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
};


platform.CustomizedAccountsNewOrEdit={
	'initBtn':function(){

		$(".btn-Customized-account-create").click(function(){//新增绑定账号
			$.showLoading();
			$.ajax({
				type: "post",
				url: global.baseUrl+'platform/customized-accounts/save',
				data:$("#platform-CustomizedAccounts-form").serialize(),
				dataType:'json',
				success:function (data){
				   $.hideLoading();
				   if (data["code"]=="fail")  {
					   bootbox.alert({title:Translator.t('错误提示') , message:data["message"] });	
					   return false;
				   }else{
					   bootbox.alert({title:Translator.t('提示'),message:Translator.t('成功创建'),callback:function(){
								window.location.reload();
								$.showLoading();
							}
					   });
				   }
				},
				error: function(){
					$.hideLoading();
					bootbox.alert({ title:Translator.t('提示') , message:Translator.t('后台传输出错!') });
				}		
			});
			
		});	
		$(".btn-Customized-account-save").click(function(){ // 编辑账号信息
			$.showLoading();
			$.ajax({
				type: "post",
				url: global.baseUrl+'platform/customized-accounts/save',
				data:$("#platform-CustomizedAccounts-form").serialize(),
				dataType:'json',
				success:function (data){
				   $.hideLoading();
				   if (data["code"]=="fail")  {
					   bootbox.alert({title:Translator.t('错误提示') , message:data["message"] });	
					   return false;
				   }else{
					   bootbox.alert({title:Translator.t('提示'),message:Translator.t('修改成功'),callback:function(){
								window.location.reload();
								$.showLoading();
							}
					   });
				   }
				},
				error: function(){
					$.hideLoading();
					bootbox.alert({ title:Translator.t('提示') , message:Translator.t('后台传输出错!') });
				}		
			});
		});
		
	},
};
