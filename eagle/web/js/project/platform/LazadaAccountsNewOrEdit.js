/**
 +------------------------------------------------------------------------------
 *查看/修改Lazada账号的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		purchase
 * @subpackage  Exception
 * @author		dzt
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined') platform= new Object();

platform.LazadaAccountsNewOrEdit={
	   'initWidget': function() {
	   	    var thisObject = platform.LazadaAccountsNewOrEdit;
		    thisObject.initBtn();
		},
		'initBtn':function(){
			//新增lazada账号
            $(".btn-lazada-account-create").click(function(){
				if (!$('#platform-LazadaAccounts-form').formValidation('form_validate')){
					bootbox.alert(Translator.t('录入格式不正确!'));
					return false;
				}
            	$.showLoading();
				$.post(
					   global.baseUrl+'platform/lazada-accounts/create',$("#platform-LazadaAccounts-form").serialize(),
					   function (data){
						   $.hideLoading();
						   var retinfo = eval('(' + data + ')');
						   if (retinfo["code"]=="fail")  {
							   bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });
							   return false;
						   }else{
							   bootbox.alert({title:Translator.t('提示'),message:Translator.t('成功创建'),callback:function(){
										window.location.reload();
										$.showLoading();
									}
							   });
						   }
				    });
		    });	
            
            // 保存lazada的账号信息
            $(".btn-lazada-account-save").click(function(){
            	if (!$('#platform-LazadaAccounts-form').formValidation('form_validate')){
    				bootbox.alert(Translator.t('录入格式不正确!'));
    				return false;
    			}
    			$.showLoading();
    			$.post(
    				   global.baseUrl+'platform/lazada-accounts/update',$("#platform-LazadaAccounts-form").serialize(),
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
            });
            
            // 获取lazada的账号的授权信息
        	$(".btn-lazada-get-auth-info-save").click(function(){
            	$.showLoading();
        		$.ajax({
        			type: "post",
        			url: global.baseUrl+"platform/lazada-accounts/auth4",
        			data:$("#platform-LazadaGetAuthInfo-form").serialize() ,
        			cache: false,
        			dataType:"json",
        			success: function(data){
        				$.hideLoading();
        				if(data.code != 200){
        					bootbox.alert({title:Translator.t('错误提示') , message:data.message });	
 						   return false;
        				}else{
        					bootbox.alert({title:Translator.t('提示'),message:data.message ,callback:function(){
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
