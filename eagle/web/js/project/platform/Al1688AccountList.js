
/**
 * 解除绑定
 * dzt 2015-03-25 for tracking 界面
 */
function delUser1688(uid_1688 , aliId){
	bootbox.confirm({
		title : Translator.t('Confirm'),
		message : Translator.t('您确定要删除1688帐号?'),  
	    callback : function(r) {
			if (r){
				$.showLoading();
				$.post( global.baseUrl+'platform/al1688-accounts/delete', {'uid_1688':uid_1688,'aliId':aliId} , function (data){
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
			}
		}
	});
}
/**
 * 授权
 * lrq 2018-04-13
 */
function authorizationUser1688(){
	var left = Math.floor((Math.random()*$(window).width()/2)+1);
	var newTab=window.open('about:blank','newwindow', 'height=500,width=600,top=100,left='+left+',toolbar=no,menubar=no,scrollbars=no, resizable=no,location=no, status=no');  
	newTab.location.href = global.baseUrl+"platform/al-1688-accounts/auth1/";
}

function set1688AccountAlias(uid_1688 , aliId){
	var handle= $.openModal(global.baseUrl+"platform/al1688-accounts/setaliasbox",{uid_1688:uid_1688,aliId:aliId},'设置别名','post');  // 打开窗口命令
	handle.done(function($window){
     // 窗口载入完毕事件
     //$window.find("input[type=date]").datepicker();    // 渲染日历选择框
	 
	 $window.find("#btn_ok").on('click',function(){
		 btnObj = $(this);
		 btnObj.prop('disabled','disabled');
		  $.ajax({
				type: "POST",
				dataType: 'json',
				url:'/platform/al1688-accounts/save-alias', 
				data: $('#platform-aliexpress-setalias-form').serialize(),
				success: function (result) {
					if (result.success ){
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