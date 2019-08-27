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
 *查看/修改Rumall账号的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		purchase
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined') platform= new Object();

platform.RumallAccountsNewOrEdit={
	//mode can be new,view,edit
   'setting':{'mode':"","RumallData":""},
	'initBtn':function(){

		$(".btn-Rumall-account-create").click(function(){//新增Rumall绑定账号
				$.showLoading();
				$.ajax({
					type: "post",
					url: global.baseUrl+'platform/rumall-accounts/create',
					data:$("#platform-RumallAccounts-form").serialize(),
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
		$(".btn-Rumall-account-save").click(function(){ // 编辑指定Wish的账号信息
			platform.RumallAccountsNewOrEdit.sumbitUpdateRumallInfo();
		});
		
	},
	'sumbitUpdateRumallInfo':function(){
			$.showLoading();
			$.ajax({
				type: "post",
				url:global.baseUrl+'platform/rumall-accounts/update',
				data:$("#platform-RumallAccounts-form").serialize(),
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