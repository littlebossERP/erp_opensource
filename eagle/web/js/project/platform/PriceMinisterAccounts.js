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

platform.PriceMinisterAccountsList = {	
	'initWidget': function() {
		// 绑定新的PriceMinister账号
		$("#new-PriceMinister-account-btn").unbind('click').click(function(){
			$.get( global.baseUrl+'platform/priceminister-accounts/new',
			   function (data){
					bootbox.dialog({
						title : Translator.t("绑定新的PriceMinister账号"),
						className: "PriceMinisterAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-PriceMinister-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("新建"),  
						        className: "btn-primary btn-PriceMinister-account-create",  
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
	
	'addPriceMinisterAccount' : function(){
		$.get( global.baseUrl+'platform/priceminister-accounts/new',
			   function (data){
					bootbox.dialog({
						title : Translator.t("PriceMinister"),
						className: "PriceMinisterAccountInfo",
						buttons: {  
							Cancel: {  
						        label: Translator.t("返回"),  
						        className: "btn-default btn-PriceMinister-account-return",  
						    }, 
						    OK: {  
						        label: Translator.t("新建"),  
						        className: "btn-primary btn-PriceMinister-account-create",  
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
	
	'openViewWindow': function(pm_id){
		$.get( global.baseUrl+'platform/priceminister-accounts/view-or-edit/?'+'pm_id='+pm_id+'&mode=view',
			function (data){
				bootbox.dialog({
					className: "PriceMinisterAccountInfo",
					title : Translator.t("查看PriceMinister账号信息"),
				    message: data,
				    buttons: {
						Cancel: {
					        label: Translator.t("返回"),  
					        className: "btn-default btn-PriceMinister-account-return",  
					    }, 
					},
				});		
			}
		);
	},
	
	'openEditWindow': function(pm_id){
		$.get( global.baseUrl+'platform/priceminister-accounts/view-or-edit/?'+'pm_id='+pm_id+'&mode=edit',
			function (data){
				bootbox.dialog({
					title : Translator.t("编辑PriceMinister账号信息"),
					className: "PriceMinisterAccountInfo",
				    message: data,
				    buttons: {  
						Cancel: {  
					        label: Translator.t("返回"),  
					        className: "btn-default btn-PriceMinister-account-return",  
					    }, 
					    OK: {  
					        label: Translator.t("保存"),  
					        className: "btn-primary btn-PriceMinister-account-save",  
				            callback: function () {  
				            	return false;
				            }  
				        }  
					},
				});		
			}
		);
	},
	
	'delPriceMinisterAccount': function(pm_id){
		bootbox.confirm({  
	        title : Translator.t('Confirm'),
			message : Translator.t('您确定要删除PriceMinister帐号与平台的绑定?'),  
	        callback : function(r) {  
	        	if (r){
					$.showLoading();
					$.post(
						global.baseUrl+'platform/priceminister-accounts/delete',{pm_id:pm_id},
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


platform.PriceMinisterAccountsNewOrEdit={
	'initBtn':function(){

		$(".btn-PriceMinister-account-create").click(function(){//新增PriceMinister绑定账号
			$.showLoading();
			$.ajax({
				type: "post",
				url: global.baseUrl+'platform/priceminister-accounts/create',
				data:$("#platform-PriceMinisterAccounts-form").serialize(),
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
		$(".btn-PriceMinister-account-save").click(function(){ // 编辑指定PriceMinister的账号信息
			platform.PriceMinisterAccountsNewOrEdit.sumbitUpdatePriceMinisterInfo();
		});
		
	},
	'sumbitUpdatePriceMinisterInfo':function(){
		$.showLoading();
		$.ajax({
			type: "post",
			url:global.baseUrl+'platform/priceminister-accounts/update',
			data:$("#platform-PriceMinisterAccounts-form").serialize(),
			dataType:'json',
			success:function (data){
			   $.hideLoading();
			   if (data["code"]=="fail")  {
				   bootbox.alert({title:Translator.t('错误提示') , message:data["message"] });	
				   return false;
			   }else{
				   bootbox.alert({title:Translator.t('提示'),message:Translator.t('成功更新'),callback:function(){
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
	}
};

function setPriceMinisterAccountSync(val,user,sel){
	$.showLoading();
	$.ajax({
		type: "post",
		url: global.baseUrl+"platform/priceminister-accounts/set-priceminister-account-sync",
		data: {setval:val,setuser:user,setitem:sel},
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
