
/**
  +------------------------------------------------------------------------------
 * 编辑用户视图js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		permission
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof permission === 'undefined')  permission = new Object();
permission.userEdit = {
	init : function(){
		$("#platform-all").click(function(){
			if($(this).prop("checked")==true){
				$('.platform_select_all').prop("checked",true);
				$('.single_select').prop("checked",true);
				$("input[name='platforms[]']").prop("checked",true);
			}else{
				$('.platform_select_all').prop("checked",false);
				$('.single_select').prop("checked",false);
				$("input[name='platforms[]']").prop("checked",false);
			}
		});
		
		$(".platform_select_all").click(function(){
			var platform = $(this).data("platform");
			if($(this).prop("checked")==true){
				$("input[name='"+platform+"[]']").prop("checked",true);
				$("input[name='platforms[]'][data-platform='"+platform+"']").prop("checked",true);
				$("#"+platform+"_full_permission_tip").css('display','inline-block');
			}else{
				$("input[name='"+platform+"[]']").prop("checked",false);
				$("input[name='platforms[]'][data-platform='"+platform+"']").prop("checked",false);
				$("#"+platform+"_full_permission_tip").css('display','none');
			}
			
		});
		
		$('[name="platforms[]"]:not(#platform-all)').click(function(){
			if($(this).prop("checked")==true){
				if($('[name="platforms[]"]:not(#platform-all)').length == $('[name="platforms[]"]:not(#platform-all):checked').length)
					$("#platform-all").prop("checked",true);
			}else{
				$("#platform-all").prop("checked",false);
			}
		});
		
		$('.btn-user-account-save').click(function(){
			permission.userEdit.parentSave();
		});
		
		$("#module-inventory").click(function(){
			if($(this).prop("checked")==true){
				$('#module-inventory-edit').prop("checked",true);
			}else{
				$('#module-inventory-edit').prop("checked",false);
			}
		});
		$("#module-inventory-edit").click(function(){
			if($(this).prop("checked")==true){
				$('#module-inventory').prop("checked",true);
			}
		});
		$("#module-catalog").click(function(){
			if($(this).prop("checked")==true){
				$('#module-catalog-edit').prop("checked",true);
				$('input[id^="catalog_"]').prop("checked",true);
			}else{
				$('#module-catalog-edit').prop("checked",false);
				$('input[id^="catalog_"]').prop("checked",false);
			}
		});
		$("#module-catalog-edit").click(function(){
			if($(this).prop("checked")==true){
				$('#module-catalog').prop("checked",true);
			}
		});
		$('input[id^="catalog_"]').click(function(){
			if($(this).prop("checked")==true){
				$('#module-catalog').prop("checked",true);
			}
		});
	},
	
	save : function(goHome){
		if( $('input[name=formerpassword]').val().length < 1 ){
    		$('input[name=formerpassword]').parent().addClass('has-error');
    		bootbox.alert({message:'请输入原密码'});
    		return ;
    	} else {
    		$('input[name=formerpassword]').parent().removeClass('has-error');
    		$('input[name=formerpassword]').parent().addClass('has-success');
    	}
		
		if( $('input[name=password]').val().length < 6 ){
    		$('input[name=password]').parent().addClass('has-error');
    		bootbox.alert({message:'密码长度不得少于六个字符'});
    		return ;
    	} else {
    		$('input[name=password]').parent().removeClass('has-error');
    		$('input[name=password]').parent().addClass('has-success');
    	}
    	if ( $('input[name=repassword]').val().length < 6 ){
    		$('input[name=repassword]').parent().addClass('has-error');
    		bootbox.alert({message:'确认密码长度不得少于六个字符'});
    		return ;
    	} else {
    		$('input[name=repassword]').parent().removeClass('has-error');
    		$('input[name=repassword]').parent().addClass('has-success');
    	}
    	if ( $('input[name=repassword]').val() != $('input[name=password]').val() ){
    		$('input[name=repassword]').parent().addClass('has-error');
    		bootbox.alert({message:'确认密码与密码不一致'});
    		return ;
    	} else {
    		$('input[name=repassword]').parent().removeClass('has-error');
    		$('input[name=repassword]').parent().addClass('has-success');
    	}
		
		$.ajax({
            type:'post',
            url: $('#permission-user-edit').attr('action'),
            data:$('#permission-user-edit').serialize(),
            dataType: 'json',
            success: function(data){
            	if(data.code == 200){
                	bootbox.alert({title: Translator.t("成功") , message:data.message,callback:function(){
                		if(goHome && goHome == true)
                			window.location.href = "/";
                		else
                			window.location.reload();
					    $.showLoading();
				   	}});
                }else{
                    bootbox.alert({message:data.message,});
                }
            },
            cache:false,
            complete: function(XMLHttpRequest, textStatus){
            	
            }
        });
	},
	
	parentSave : function(goHome){
		if( typeof $('input[name=qq]').val() != 'undefined' && $('input[name=qq]').val().length < 1 ){
    		$('input[name=qq]').parent().addClass('has-error');
    		bootbox.alert({message:'请输入对应QQ号'});
    		return ;
    	} else {
    		$('input[name=qq]').parent().removeClass('has-error');
    		$('input[name=qq]').parent().addClass('has-success');
    	}
		
		if($('input[name=password]').val().length > 0){
			if( $('input[name=password]').val().length < 6 ){
	    		$('input[name=password]').parent().addClass('has-error');
	    		bootbox.alert({message:'密码长度不得少于六个字符'});
	    		return ;
	    	} else {
	    		$('input[name=password]').parent().removeClass('has-error');
	    		$('input[name=password]').parent().addClass('has-success');
	    	}
		}
		
		$.ajax({
            type:'post',
            url: $('#permission-user-edit').attr('action'),
            data:$('#permission-user-edit').serialize(),
            dataType: 'json',
            success: function(data){
            	if(data.code == 200){
                	bootbox.alert({title: Translator.t("成功") , message:data.message,callback:function(){
                		if(goHome && goHome == true)
                			window.location.href = "/";
                		else
                			window.location.reload();
					    $.showLoading();
				   	}});
                }else{
                    bootbox.alert({message:data.message,});
                }
            },
            cache:false,
            complete: function(XMLHttpRequest, textStatus){
            	
            }
        });
	},
	
	saveFamilyname : function(){
		if( $('input[name=edit_familyname]').val().length < 1 ){
    		$('input[name=edit_familyname]').parent().addClass('has-error');
    		bootbox.alert({message:'用户名不能为空'});
    		return ;
    	} else {
    		$('input[name=edit_familyname]').parent().removeClass('has-error');
    		$('input[name=edit_familyname]').parent().addClass('has-success');
    	}
		var familyname = $('input[name=edit_familyname]').val();
		var user_id = $('input[name=edit_user_id]').val();
		
		$.ajax({
            type:'post',
            url: '/permission/user/save-familyname',
            data:{'familyname': familyname, 'user_id': user_id},
            dataType: 'json',
            success: function(res){
            	if(res.success){
                	bootbox.alert('设置成功');
                }else{
                    bootbox.alert(res.msg);
                }
            },
            cache:false,
            complete: function(XMLHttpRequest, textStatus){
            	
            }
        });
	},

};


