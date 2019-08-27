/**
 +------------------------------------------------------------------------------
 * app的详情界面js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		app
 * @subpackage  Exception
 * @author		lolo <jiongqiang.xiao@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof appManager === 'undefined')  appManager = new Object();
appManager.view={
		'init': function() {
			
			$('#appTab a:first').tab('show');
			
			$("#view-app-install-btn").click(function(){				    
					appKey=$(this).parent().attr("appkey");
					appName=$(this).parent().attr("appname");
					bootbox.confirm(Translator.t( "是否确定启用app---" )+appName, function(result) {					
						if (result){
						   $.showLoading();
						   $.post(global.baseUrl+'app/app/install',{'key':appKey},
							   	 function (data){	
							         $.hideLoading();
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
			$("#view-app-unactivate-btn").click(function(){				
				appKey=$(this).parent().attr("appkey");
				appName=$(this).parent().attr("appname");
				bootbox.confirm(Translator.t( "是否确定停用app---" )+appName, function(result) {					
					if (result){
						$.showLoading();
					   $.post(global.baseUrl+'app/app/unactivate',{'key':appKey},
						   	 function (data){
						         $.hideLoading();
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
				
		},
}

$(function() {
//	appManager.view.init();
	
});

