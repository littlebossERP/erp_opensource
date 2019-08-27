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
 *查看/修改Cdiscount账号的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		purchase
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined') platform= new Object();

platform.CdiscountAccountsNewOrEdit={
	//mode can be new,view,edit
   'setting':{'mode':"","CdiscountData":""},
	'initBtn':function(){

		$(".btn-Cdiscount-account-create").click(function(){//新增Cdiscount绑定账号
			$.showLoading();
			$.ajax({
				type: "post",
				url: global.baseUrl+'platform/cdiscount-accounts/create',
				data:$("#platform-CdiscountAccounts-form").serialize(),
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
		$(".btn-Cdiscount-account-save").click(function(){ // 编辑指定Wish的账号信息
			platform.CdiscountAccountsNewOrEdit.sumbitUpdateCdiscountInfo();
		});
		
	},
	'sumbitUpdateCdiscountInfo':function(){
		$.showLoading();
		$.ajax({
			type: "post",
			url:global.baseUrl+'platform/cdiscount-accounts/update',
			data:$("#platform-CdiscountAccounts-form").serialize(),
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