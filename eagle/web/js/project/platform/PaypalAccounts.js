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
 * Paypal账号绑定设置js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		platform
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined') platform= new Object();

platform.PaypalAccountsList = {	
	'initWidget': function() {
		// 绑定新的Paypal账号
		$("#new-Paypal-account-btn").unbind('click').click(function(){
			$.get( global.baseUrl+'platform/paypal-accounts/new',
			   function (data){
					bootbox.dialog({
						title : Translator.t("绑定新的Paypal账号"),
						className: "PaypalAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Paypal-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("新建"),  
						        className: "btn-primary btn-Paypal-account-create",  
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
	
	'addPaypalAccount' : function(){
		$.get( global.baseUrl+'platform/paypal-accounts/new',
			   function (data){
					bootbox.dialog({
						title : Translator.t("Paypal"),
						className: "PaypalAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-Paypal-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("新建"),  
						        className: "btn-primary btn-Paypal-account-create",  
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

	'delPaypalAccount': function(ppid){
		bootbox.confirm({  
	        title : Translator.t('Confirm'),
			message : Translator.t('您确定要删除Paypal帐号与平台的绑定?'),  
	        callback : function(r) {  
	        	if (r){
					$.showLoading();
					$.post(
						global.baseUrl+'platform/paypal-accounts/delete',{ppid:ppid},
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
	
	'switchOverwriteEbay' : function (ppid,canOverwrite){
		if(canOverwrite=='Y')
			var ov_ch = '开启使用';
		else
			var ov_ch = '停止使用';
		bootbox.confirm({  
	        title : Translator.t('Confirm'),
			message : Translator.t('您确定要'+ov_ch+'Paypal买家地址覆盖ebay订单的买家地址?'),  
	        callback : function(r) {  
	        	if (r){
					$.showLoading();
					$.post(
						global.baseUrl+'platform/paypal-accounts/switch-overwrite-ebay',{ppid:ppid,overwrite:canOverwrite},
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


platform.PaypalAccountsNewOrEdit={
	'initBtn':function(){

		$(".btn-Paypal-account-create").click(function(){//新增Paypal绑定账号
			$.showLoading();
			$.ajax({
				type: "post",
				url: global.baseUrl+'platform/paypal-accounts/create',
				data:$("#platform-PaypalAccounts-form").serialize(),
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
		$(".btn-Paypal-account-save").click(function(){ // 编辑指定Paypal的账号信息
			platform.PaypalAccountsNewOrEdit.sumbitUpdatePaypalInfo();
		});
		
	},
};
