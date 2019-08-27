if (typeof site === 'undefined')  site = new Object();
site.userGuide2 = {
	init : function(){
//		currentScrollTop = jQuery(window).scrollTop();
//		preventDocumentScroll = function(){
//			if($(".modal.fade").css("display")!="none"){//弹出窗口时不允许滚动条滚动
//			     $(window).scrollTop(currentScrollTop);
//			}
//		},
//		
//		$(".bootbox-close-button").on('click',function(){
//			$(window).off("scroll", preventDocumentScroll);
//		});
//		
//		jQuery(window).scroll(preventDocumentScroll);
		
		$(':checkbox').prop('checked',true); // 根据第一步被选中的app 默认全部添加
		
		$('.nextStep').click(function(){
			var btnNextStep = this;
			$.showLoading();
			$.ajax({
	            type:'post',
	            url: global.baseUrl+'site/user-guide-3',
	            data: $('#user-guide-2-form').serialize(),
	            cache:false,
	            dataType: 'json',
	            success: function(data){
	            	debugger
	            	if(data.code == 200){
	            		$(btnNextStep).parents('.modal').modal("hide");
		            	window.location.href = global.baseUrl+'app/app/list';
	            	}else{
	            		$.hideLoading();
	            		 bootbox.alert({title:data.code,message:data.message,});
	            	}
	            },
	        });
		});
	},
	
	// app 详情
	appDetailView : function(appKey , appName){
		$.showLoading();
		$.get( global.baseUrl+'app/app/view?key='+appKey, {showOperation:0} ,  function (data){
			$.hideLoading();
			bootbox.dialog({
				className: "user-guide-view-app-modal", 
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
	},
	
	
}