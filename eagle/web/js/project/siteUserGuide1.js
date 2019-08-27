if (typeof site === 'undefined')  site = new Object();
site.userGuide1 = {
	init : function(){
		$('.nextStep').click(function(){
			$.showLoading();
			$.ajax({
	            type:'post',
	            url: global.baseUrl+'site/user-guide-2',
	            data:$('#user-guide-1-form').serialize(),
	            cache:false,
	            success: function(data){
	            	$('#user-guide-1-form').parents('.modal').modal("hide");
	            	
	            	setTimeout(function(){
	            		$.hideLoading();
	            		bootbox.dialog({
		            		className: 'user-guide-view-app-modal',
							title : Translator.t("使用向导(2/2)"),
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
	            		
	            	} , 300)
	            	
	            },
	        });
		});
		
	},
}