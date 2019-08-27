/**
 +------------------------------------------------------------------------------
 * Amazon账号列表的界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		platform
 * @subpackage  Exception
 * @author		dzt <zhitian.deng@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
function isJSON(str) {
    if (typeof str == 'string') {
        try {
            JSON.parse(str);
            return true;
        } catch(e) {
            console.log(e);
            return false;
        }
    }
    console.log('It is not a string!')    
}
if (typeof platform === 'undefined')  platform = new Object();
platform.amazonAccountsList = {	
		
	'initWidget': function() {	
		
		// 新建amazon账号
		$("#new-amazon-account-btn").unbind('click').click(function(){
			$.get( global.baseUrl+'platform/amazon-accounts/new',
			   function (data){
			   		if (isJSON(data)) {
			   			var jsonData=JSON.parse(data);
			   			if (jsonData.success) {
					   		$.alert(jsonData.msg);
					   		return false;
					   	}
			   		}
					bootbox.dialog({
						title : Translator.t("新建amazon账号"),
						buttons: {  
							Cancel: {  
		                        label: Translator.t("返回"),  
		                        className: "btn-default btn-amazon-account-return",  
		                    }, 
		                    OK: {  
		                        label: Translator.t("新建"),  
		                        className: "btn-primary btn-amazon-account-create",  
//		                        callback: function () {  
//		                        	debugger
//		                        }  
		                    }  
						},
					    message: data,
					});		
			});
		});
		
		// 懒得为每个ajax请求添加 overlay 了，这里统一添加。
		$(document).ajaxStart(function () {
			$.showLoading();
		}).ajaxStop(function () {
			$.hideLoading();
		});
	},
	
	// 查看amazon账号信息
	'openViewWindow': function(amazon_uid){
		$.get( global.baseUrl+'platform/amazon-accounts/view-or-edit/?'+'amazon_uid='+amazon_uid+'&mode=view',
		   function (data){
				bootbox.dialog({
					title : Translator.t("查看amazon账号信息"),
					buttons: {  
						Cancel: {  
	                        label: Translator.t("返回"),  
	                        className: "btn-default btn-amazon-account-return",  
	                    }, 
					},
				    message: data,
				});		
		});
	},
	
	// 编辑amazon账号信息
	'openEditWindow': function(btn, amazon_uid){
		// 原本使用even，Firefox不支持
		var editButton = btn;
		$(editButton).attr('disabled','disabled');
		$.get( global.baseUrl+'platform/amazon-accounts/view-or-edit/?'+'amazon_uid='+amazon_uid+'&mode=edit',
		   function (data){
				bootbox.dialog({
					title : Translator.t("编辑amazon账号信息"),
					buttons: {  
						Cancel: {  
	                        label: Translator.t("返回"),  
	                        className: "btn-default btn-amazon-account-return",  
	                    }, 
	                    OK: {  
	                        label: Translator.t("保存"),  
	                        className: "btn-primary btn-amazon-account-save",  
	                        callback: function () {
	                        	return false;
	                        }  
	                    }  
					},
				    message: data,
				});	
				$(editButton).removeAttr('disabled')
		});
		
	},
	
	// 重新授权amazon账号信息
	'openReauthWindow': function(btn, amazon_uid){
		// 原本使用even，Firefox不支持
		var editButton = btn;
		$(editButton).attr('disabled','disabled');
		$.get( global.baseUrl+'platform/amazon-accounts/view-or-edit/?'+'amazon_uid='+amazon_uid+'&mode=reauth',
		   function (data){
				bootbox.dialog({
					title : Translator.t("重新授权amazon账号"),
					buttons: {  
						Cancel: {  
	                        label: Translator.t("返回"),  
	                        className: "btn-default btn-amazon-account-return",  
	                    }, 
	                    OK: {  
	                        label: Translator.t("保存"),  
	                        className: "btn-primary btn-amazon-account-save",  
	                        callback: function () {
	                        	return false;
	                        }  
	                    }  
					},
				    message: data,
				});	
				$(editButton).removeAttr('disabled')
		});
		
	},
	
	// 开启/关闭amazon账号同步
	'switchsync': function(amazon_uid , is_active){
		$.showLoading();
		$.post( global.baseUrl+'platform/amazon-accounts/switch-sync',{'amazon_uid':amazon_uid,'is_active':is_active},
		   function (data){
			$.hideLoading();
			var retinfo = eval('(' + data + ')');
			if (retinfo["code"]=="fail")  {
				bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
				return false;
			}else{
				bootbox.alert({title:Translator.t('提示'),message:retinfo["message"],callback:function(){
					window.location.reload();
					$.showLoading();
				}});
			}
		});
	},
	
	'unbindAmazonAccount': function(amazon_uid,storeName){
		bootbox.confirm({  
	        title : Translator.t('Confirm'),
			message : Translator.t('您确定要解绑'+storeName+'帐号?'),  
	        callback : function(r) {  
	        	if (r){
					$.showLoading();
					$.post(
						global.baseUrl+'platform/amazon-accounts/unbind',{amazon_uid:amazon_uid},
						function (result){
							$.hideLoading();
							var retinfo = eval('(' + result + ')');
							if (retinfo["code"]=="fail")  {
								bootbox.alert({title:Translator.t('错误提示') , message:retinfo["message"] });	
								return false;
							}else{
								bootbox.alert({title:Translator.t('提示'),message:retinfo["message"],
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
	
	// 新建amazon账号
	'openNewWindow': function(amazon_uid){
		$.get( global.baseUrl+'platform/amazon-accounts/new',
		   function (data){
		   		if (isJSON(data)) {
		   			var jsonData=JSON.parse(data);
		   			if (jsonData.success) {
				   		$.alert(jsonData.msg);
				   		return false;
				   	}
		   		}
				bootbox.dialog({
					title : Translator.t("新建amazon账号"),
					buttons: {  
						Cancel: {  
	                        label: Translator.t("返回"),  
	                        className: "btn-default btn-amazon-account-return",  
	                    }, 
	                    OK: {  
	                        label: Translator.t("新建"),  
	                        className: "btn-primary btn-amazon-account-create",  
//	                        callback: function () {  
//	                        	debugger
//	                        }  
	                    }  
					},
				    message: data,
				});		
		});
		
	},
	
	'addMarketpalce' : function(amazon_uid , store_name ){
		$.get( global.baseUrl+'platform/amazon-accounts/add-marketplace-view?'+'amazon_uid='+amazon_uid,
		   function (data){
				bootbox.dialog({
					title : store_name + ' ' + Translator.t("添加 Marketplace"),
					buttons: {  
						Cancel: {  
	                        label: Translator.t("返回"),  
	                        className: "btn-default btn-amazon-account-return",  
	                    }, 
	                    OK: {  
	                        label: Translator.t("添加"),  
	                        className: "btn-primary btn-amazon-account-add-marketplace",  
//	                        callback: function () {  
//	                        	debugger
//	                        }  
	                    }  
					},
				    message: data,
				});		
		});
				
	},

};
