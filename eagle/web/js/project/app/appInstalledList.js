/**
 +------------------------------------------------------------------------------
 * app的列表界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		app
 * @subpackage  Exception
 * @author		lolo <jiongqiang.xiao@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof appManager === 'undefined')  appManager = new Object();
appManager.installed_list={
		
		'init': function() {
			
			//点击某个app对应的"停用"按钮
			$(".app-unactivate-btn").click(function(){
				appKey=$(this).parent().parent().attr("appkey");
				appName=$(this).parent().parent().attr("appname");
				bootbox.confirm(Translator.t( "是否确定停用app---" )+appName, function(result) {					
					if (result){
					   $.post(global.baseUrl+'app/app/unactivate',{'key':appKey},
						   	 function (data){					   		             
							   	 var retinfo = eval('(' + data + ')');
								 if (retinfo["code"]=="fail")  {
									  bootbox.alert(retinfo["message"], function() {});
									  return false;
								 }								 
								 
								 bootbox.alert(Translator.t("app停用成功"), function() {
									window.location.reload();
								 });
						      }
					    );						
					}
					
				});
				
			});	
		    
			
			//点击某个app对应的"启用"按钮
			$(".app-activate-btn").click(function(){
				appKey=$(this).parent().parent().attr("appkey");
				appName=$(this).parent().parent().attr("appname");
				bootbox.confirm(Translator.t( "是否确定启用app---" )+appName, function(result) {					
					if (result){
					   $.post(global.baseUrl+'app/app/activate',{'key':appKey},
						   	 function (data){					   		             
							   	 var retinfo = eval('(' + data + ')');
								 if (retinfo["code"]=="fail")  {
									  bootbox.alert(retinfo["message"], function() {});
									  return false;
								 }								 
								 
								 bootbox.alert(Translator.t("app启用成功"), function() {
									window.location.reload();
								 });
						      }
					    );						
					}
					
				});
				
			});			
			
			
			//打开指定app的参数设置界面
			$(".app-configset-btn").click(function(){
				appKey=$(this).parent().parent().attr("appkey");
				appName=$(this).parent().parent().attr("appname");
				$.get( global.baseUrl+'app/appconfig/view?key='+appKey,
						 function (data){
								$.hideLoading();
								bootbox.dialog({
									closeButton: true, 
									className: "app-configset-modal", 
									backdrop: false,
									title : appName,									
									buttons: {  
										Cancel: {  
					                        label: Translator.t("返回"),  
					                        className: "btn-default",  
					                    },
					                    success: {
					                        label:"保存",
					                        className:"appconfig-save-btn btn-info",
					                        callback: function() {
					                          return false; //不要自动关闭弹出的对话框
					                        }
					                    }
					                   
									},
								    message: data,
								});	
					});				
			});			
			
			

		},
}

$(function() {
	appManager.installed_list.init();
	
});

