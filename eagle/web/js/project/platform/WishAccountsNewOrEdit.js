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
 *查看/修改Wish账号的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		purchase
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined') platform= new Object();

platform.WishAccountsNeworedit={
	    //mode can be new,view,edit
       'setting':{'mode':"","WishData":""},
	   'initWidget': function() {
		   $('#geApiKey_imgBlock img').attr('src',global.baseUrl+'images/wish/get_api_key.png');
			
			$('#geApiKey_imgBlock img').click(function(){
				bootbox.dialog({
					title : Translator.t("如何获取API key"),
					className: "imgBlock",
				    message: $('#geApiKey_imgBlock').html(),
				});		
			})
			
			$("#platform-WishAccounts-form input[name='store_name']").formValidation({validType:['trim','length[1,50]'],tipPosition:'left',required:true});
			//$("#platform-WishAccounts-form input[name='token']").formValidation({validType:['trim','length[84,84]'],tipPosition:'left',required:true});
			
			
	   	    var thisObject=platform.WishAccountsNeworedit;
		    thisObject.initBtn();
		},
		'initBtn':function(){

            $(".btn-Wish-account-create").click(function(){//新增Wish账号
				if (! $('#platform-WishAccounts-form').formValidation('form_validate')){
					bootbox.alert(Translator.t('录入格式不正确!'));
					return false;
				}
            	$.showLoading();
				var left = Math.floor((Math.random()*$(window).width()/2)+1);
				var w = window.open(global.baseUrl,'newwindow', 'height=500,width=600,top=100,left='+left+',toolbar=no,menubar=no,scrollbars=no, resizable=no,location=no, status=no');
				
				$.ajax({
					type: "POST",
					dataType: 'json',
					url:  global.baseUrl+'platform/wish-accounts-v2/create',
					data: $("#platform-WishAccounts-form").serialize(),
					success: function (data) {
						$.hideLoading();
						if (data.code == "fail"){
							bootbox.alert(data.message);
						}else{
							var left = Math.floor((Math.random()*$(window).width()/2)+1);
					 
							w.location = global.baseUrl+'platform/wish-accounts-v2/auth1?site_id='+data.site_id;
							 						   
						}
						return true;
					},
					error :function () {
						w.close();
						return false;
					}
				});
		 
					
		    });	
            $(".btn-Wish-account-save").click(function(){ // 编辑指定Wish的账号信息
				platform.WishAccountsNeworedit.sumbitUpdateWishInfo();
            });
		    
		},
		'sumbitUpdateWishInfo':function(){
			if (! $('#platform-WishAccounts-form').formValidation('form_validate')){
				bootbox.alert(Translator.t('录入格式不正确!'));
				return false;
			}
			$.showLoading();
			$.post(
				   global.baseUrl+'platform/wish-accounts/update',$("#platform-WishAccounts-form").serialize(),
				   function (data){
					   $.hideLoading();
					   var retinfo = eval('(' + data + ')');
					   if (retinfo["code"]=="fail")  {
						   bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
						   return false;
					   }else{
						   bootbox.alert({title:Translator.t('提示'),message:Translator.t('成功更新'),callback:function(){
									window.location.reload();
									$.showLoading();
								}
						   });
					   }
			});
		},
		
};
