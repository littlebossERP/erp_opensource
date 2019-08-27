/**
 +------------------------------------------------------------------------------
 * newegg账号列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		platform
 * @subpackage  Exception
 * @author		winton
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined') platform= new Object();

platform.neweggAccountsList={
	
	'openNeweggAccountInfoWindow' : function($site_id){
		$title = '新建newegg账号';
		if($site_id > 0){
			$title = '编辑newegg账号';
		}
		$.openModal("/platform/newegg-accounts/account-info-window",{site_id:$site_id},$title,"post");
	},
	
	'saveNeweggAccountInfo' : function(){
		$.showLoading();
		$.ajax({
			url: global.baseUrl + "platform/newegg-accounts/save-account-info",
			data: $('#neweggAccountInfoForm').serialize(),
			type: 'post',
			dataType: 'json',
			success: function(response) {
				$.hideLoading();
				if(response.code == 200){
					$e = $.alert(response.message, 'success');
					$e.then(function(){
						location.reload();
					});
				}else{
					$.alert(response.message, 'danger');
				}
			},
			error: function(XMLHttpRequest, textStatus) {
				$.hideLoading();
				alert('网络不稳定.请求失败,请重试');
			}
		});
	},
	
	'setNeweggAccountInfo' : function($site_id, $is_active){
		$.showLoading();
		$.ajax({
			url: global.baseUrl + "platform/newegg-accounts/set-account-sync",
			data: {
				site_id : $site_id,
				is_active : $is_active,
			},
			type: 'post',
			dataType: 'json',
			success: function(response) {
				$.hideLoading();
				if(response.code == 200){
					$e = $.alert(response.message, 'success');
					$e.then(function(){
						location.reload();
					});
				}else{
					$.alert(response.message, 'danger');
				}
			},
			error: function(XMLHttpRequest, textStatus) {
				$.hideLoading();
				alert('网络不稳定.请求失败,请重试');
			}
		});
	},
	
	'delNeweggAccountInfo' : function($site_id, $store_name){
		var event = $.confirmBox('<div class="text-danger">您确定需要移除 Newegg账号( '+$store_name+' )吗 ?</div>');
		event.then(function(){
			$.showLoading();
			$.ajax({
				url: global.baseUrl + "platform/newegg-accounts/delete-account",
				data: {
					site_id : $site_id,
					store_name : $store_name,
				},
				type: 'post',
				dataType: 'json',
				success: function(response) {
					$.hideLoading();
					if(response.code == 200){
						var e = $.alert(response.message, 'success');
						e.then(function(){
							location.reload();
						});
					}else{
						$.alert(response.message, 'danger');
					}
				},
				error: function(XMLHttpRequest, textStatus) {
					$.hideLoading();
					alert('网络不稳定.请求失败,请重试');
				}
			});
		});
	}
	
};