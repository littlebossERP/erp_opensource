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
appManager.list={
		'categoryKeyMapJson':"",
		'init': function() {
			
			//点击分类
			$(".filter-ul li a").click(function(){
				$(".filter_wrap li").removeClass("active");
				$(this).parent().addClass("active");
				
				categoryId=$(this).parent().attr("categoryid");
				if (categoryId==-1){
					//列出所有
					$(".app-list-di-wrap dl").show();
					return;
				}
				appkeyList=appManager.list.categoryKeyMapJson[categoryId];
				$(".app-list-di-wrap dl").hide();
				for(i=0;i<appkeyList.length;i++){
					$(".app-list-di-wrap").find("[appkey='"+appkeyList[i]+"']").show();					
				}
			});
			
			//点击"停用"按钮			
			$(".app-list-unactivate-btn").click(function(){
				appKey=$(this).parent().parent().attr("appkey");
				appName=$(this).parent().parent().attr("appname");
				bootbox.confirm(Translator.t( "是否确定停用app---" )+appName, function(result) {					
					if (result){
					   $.post(global.baseUrl+'app/app/unactivate',{'key':appKey},
						   	 function (data){					   		             
							   	 var retinfo = eval('(' + data + ')');
								 if (retinfo["code"]=="fail")  {
								//	  $.messager.alert('提示',retinfo["message"],'error');
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
			
			//点击"安装app"按钮
			$(".app-install-btn").click(function(){
				appKey=$(this).parent().parent().attr("appkey");
				appName=$(this).parent().parent().attr("appname");
				bootbox.confirm(Translator.t( "是否确定启用app---" )+appName, function(result) {					
					if (result){
					   $.post(global.baseUrl+'app/app/install',{'key':appKey},
						   	 function (data){					   		             
							   	 var retinfo = eval('(' + data + ')');
								 if (retinfo["code"]=="fail")  {
								//	  $.messager.alert('提示',retinfo["message"],'error');
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
		    
			
			//查看指定app的详情
			$(".app-detail-btn").click(function(){
				appKey=$(this).parent().parent().attr("appkey");
				appName=$(this).parent().parent().attr("appname");
				$.get( global.baseUrl+'app/app/view?key='+appKey,
						 function (data){
								$.hideLoading();
								bootbox.dialog({
									closeButton: true, 
									className: "view-app-modal", 
									backdrop: false,
									title : appName,
									
									buttons: {  
										Cancel: {  
					                        label: Translator.t("返回"),  
					                        className: "btn-default",  
					                    }, 
					                   
									},
								    message: data,
								});	
					});				
			});

			$(".selection_widget a").click(function(){
				appKey=$(this).attr("appkey");
				appName=$(this).attr("appname");
				$.get( global.baseUrl+'app/app/view?key='+appKey,
						 function (data){
								$.hideLoading();
								bootbox.dialog({
									closeButton: true, 
									className: "view-app-modal", 
									backdrop: false,
									title : appName,
									
									buttons: {  
										Cancel: {  
					                        label: Translator.t("返回"),  
					                        className: "btn-default",  
					                    }, 
					                   
									},
								    message: data,
								});	
					});				
			});
			
		},
		
		// 使用向导
		'openUserGuide' : function(){
			$.ajax({
	            type:'post',
	            url: global.baseUrl+'site/user-guide-1',
	            data:$('#user-guide-1-form').serialize(),
	            cache:false,
	            success: function(data){
	            	$.hideLoading();
	            	bootbox.dialog({
						title : Translator.t("使用向导(1/2)"),
						buttons: {  
							Cancel: {  
		                        label: Translator.t("跳过向导"),  
		                        className: "btn-default btn-user-guide-return",
		                    }, 
		                    OK: {  
		                        label: Translator.t("下一步"),  
		                        className: "btn-primary nextStep",  
		                        callback: function () {
		                        	return false;
		                        }  
		                    }  
						},
					    message: data,
					});	
	            },
	        });
		}, 
}

$(function() {
	appManager.list.init();
	
});

