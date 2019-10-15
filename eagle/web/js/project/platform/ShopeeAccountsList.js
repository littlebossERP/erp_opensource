/**
 +------------------------------------------------------------------------------
 * shopee账号列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		platform
 * @subpackage  Exception
 * @author		lrq
 * @version		1.0
 +------------------------------------------------------------------------------
 */
if (typeof platform === 'undefined')  platform = new Object();
platform.ShopeeAccountsList = {	
	'initWidget': function() {	
		// 懒得为每个ajax请求添加 overlay 了，这里统一添加。
		$(document).ajaxStart(function () {
			$.showLoading();
		}).ajaxStop(function () {
			$.hideLoading();
		});
	},
	
	//弹出新增shopee账号界面
	'addShopeeAccount' : function(){
		$.ajax({
			url: global.baseUrl + 'platform/shopee-accounts/new',
			data: {},
			type: 'GET',
			async: false,
			success: function(data){
				bootbox.dialog({
					title: Translator.t("新建Shopee账号"),
					classNmae: "shopeeAccountInfo",
					buttons: {
						Cancel: {
							label: Translator.t("返回"),
							calssName: "btn-default",
						},
						OK:{
							label: Translator.t("保存"),
							className: "btn-primary btn-shopee-account-save",
							callback: function(){

								return false;
							}
						},
					},
					message: data,
				});
			}
		});
		//platform.shopeeAccountsList.setTrackerSyncBackDays();
	},
	
	//弹出编辑shopee账号界面
	'openEditWindow' : function(shopee_uid){
		$.ajax({
			url: global.baseUrl + 'platform/shopee-accounts/edit',
			data: {'shopee_uid' : shopee_uid},
			type: 'GET',
			async: false,
			success: function(data){
				bootbox.dialog({
					title: Translator.t("编辑Shopee账号"),
					classNmae: "shopeeAccountInfo",
					buttons: {
						Cancel: {
							label: Translator.t("返回"),
							calssName: "btn-default",
						},
						OK:{
							label: Translator.t("保存"),
							className: "btn-primary btn-shopee-account-save",
							callback: function(){
								return false;
							}
						},
					},
					message: data,
				});
			}
		});
	},
	
	//设置同步状态
	'setShopeeAccountSync' : function(status, shopee_uid){
		$.ajax({
			type: "POST",
			url: global.baseUrl + 'platform/shopee-accounts/set-shopee-account-sync',
			data: {'status': status, 'shopee_uid': shopee_uid},
			dataType: 'json',
			success: function(res){
				if(res.success){
					$.showLoading();
					window.location.reload();
				}
				else{
					bootbox.alert(res.message);
					return false;
				}
			},
			error: function(){
				bootbox.alert(Translator.t('数据传输错误！'));
				return false;
			}
		});
	},
	
	//解绑
	'unbindShopeeAccount' : function(shopee_uid, store_name){
		bootbox.confirm({
			title: Translator.t('解绑'),
			message: Translator.t('您确定要解绑此shpee账号 '+ store_name +' 吗？'),
			callback: function(r){
				if(r){
					$.ajax({
						type: "POST",
						url: global.baseUrl + 'platform/shopee-accounts/unbind',
						data: {'shopee_uid': shopee_uid},
						dataType: "json",
						success: function(res){
							if(res.success){
								$.showLoading();
								window.location.reload();
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
				}
			},
		});
	},
	
	'authorizationUser' : function(){
		var left = Math.floor((Math.random()*$(window).width()/2)+1);
		window.open (global.baseUrl+"platform/shopee-accounts/auth1",'newwindow', 'height=750,width=1136,top=100,left='+left+',toolbar=no,menubar=no,scrollbars=no, resizable=no,location=no, status=no');
	},
	
	'authorizationUser' : function(){
		var left = Math.floor((Math.random()*$(window).width()/2)+1);
		window.open (global.baseUrl+"platform/shopee-accounts/auth1",'newwindow', 'height=750,width=1136,top=100,left='+left+',toolbar=no,menubar=no,scrollbars=no, resizable=no,location=no, status=no');
	},
	
	
	'getOpenSourceAuth' : function (){
		var handle= $.openModal(global.baseUrl+"platform/shopee-accounts/get-auth-info-window",{},'获取授权信息','post');  // 打开窗口命令
		handle.done(function($window){
	     // 窗口载入完毕事件
			
		 $window.find("#btn_ok").on('click',function(){
			 btnObj = $(this);
			 btnObj.prop('disabled','disabled');
			  $.ajax({
					type: "POST",
					dataType: 'json',
					url:'/platform/shopee-accounts/auth4', 
					data: $('#platform-ShopeeGetAuthInfo-form').serialize(),
					success: function (result) {
						if (result.code == 200){
							$.alert(Translator.t('操作成功'));
							$window.close(); 
							window.location.reload();
						}else{
							$.alert(result.message);
							btnObj.prop('disabled','');
						}
					}
				 });
     	})
	    $window.find("#btn_cancel").on('click',function(){
            $window.close();       // 关闭当前模态框
	    })
		});
	}

};



