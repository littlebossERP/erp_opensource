/**
 +------------------------------------------------------------------------------
 * 查看/修改指定的shopee账号的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		platform
 * @subpackage  Exception
 * @author		lrq
 * @version		1.0
 +------------------------------------------------------------------------------
 */


if (typeof platform === 'undefined')   platform= new Object();

platform.shopeeAccountsNeworedit={
	'init' : function(){
		//保存账号信息
		$('.btn-shopee-account-save').click(function(){
			if(!$('#platform-shopeeAccounts-form').formValidation('form_validate')){
				bootbox.alert(Translator.t('录入格式不正确！'));
				return false;
			}
			$.ajax({
				url: global.baseUrl+'platform/shopee-accounts/save',
				data: $('#platform-shopeeAccounts-form').serialize(),
				type: 'POST',
				dataType: 'json',
				success: function(res){
					if(res.success){
						$.showLoading();
						bootbox.alert({
							title: Translator.t('提示'),
							message: res.result +" 保存成功！",
							callback: function(){
								window.location.reload();
							}
						});
					}
					else{
						bootbox.alert(res.msg);
						return false;
					}
				},
				error: function(){
					bootbox.alert(Translator.t('数据传输错误！'));
					return false;
				}
			});
		});
	}



}











