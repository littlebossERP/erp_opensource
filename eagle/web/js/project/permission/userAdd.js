/**
  +------------------------------------------------------------------------------
 * 添加用户子账户视图js
 +------------------------------------------------------------------------------
 * @category	js/project
 * @package		message
 * @subpackage  Exception
 * @version		1.0
 +------------------------------------------------------------------------------
 */

if (typeof permission === 'undefined')  permission = new Object();
permission.userAdd = {
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
		
		$('.btn-user-add-account-save').click(function(){
			permission.userAdd.save();
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
			}else{
				$('#module-catalog-edit').prop("checked",false);
			}
		});
		$("#module-catalog-edit").click(function(){
			if($(this).prop("checked")==true){
				$('#module-catalog').prop("checked",true);
			}
		});
	},

	save: function(){
		if(!permission.userAdd.veriEmailFormat($('input[name=email]').val())){
			permission.userAdd.showTip($('input[name=email]') , '邮箱格式不合法');
			return ;
		}else{
			permission.userAdd.hideTip($('input[name=email]'));
		}
		
		if(!permission.userAdd.veriQQFormat($('input[name=qq]').val())){
			permission.userAdd.showTip($('input[name=qq]') , 'QQ号码不合法');
			return ;
		}else{
			permission.userAdd.hideTip($('input[name=qq]'));
		}
		
		if( $('input[name=password]').val().length < 6 ){
			permission.userAdd.showTip($('input[name=password]') , '密码长度不得少于六个字符');
			return ;
		} else {
			permission.userAdd.hideTip($('input[name=password]'));
		}
		
		$.ajax({
			type:'post',
			url: $('#permission-user-add').attr('action'),
			data:$('#permission-user-add').serialize(),
			dataType: 'json',
			success: function(data){
				var obj = [];
				if(data.code == 200){
					bootbox.alert({title: Translator.t("成功") , message:data.message,callback:function(){
						 window.location.reload();
						$.showLoading();
				   	}});
				}else if(data.code == 401){// email 出错
					obj = $('input[name=email]');
				}else if(data.code == 402){// 用户名 出错
					obj = $('input[name=qq]');
				}else if(data.code == 403){// password 出错
					obj = $('input[name=password]');
				}else{
					bootbox.alert({title:data.code,message:data.message,});
				}
				
				if(obj.length > 0){
					permission.userAdd.showTip(obj,data.message);
				}
			},
			cache:false,
			complete: function(XMLHttpRequest, textStatus){
				
			}
		});
	},
	
	// 验证邮箱格式
	veriEmailFormat : function(txt){
		return /^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-])+/i.test(txt);
	},
	
	// 验证QQ格式
	veriQQFormat : function(txt){
		return /^[0-9]{4,15}$/.test(txt);
	},
	
	showTip : function(obj,content){
		$(obj).focus();
		$(obj).parent().addClass('has-error');
		$(obj).qtip({
			content: {
				text: content,
			},
			position : {
				at : 'middle right',
				my : 'middle left',
				container: $(obj).parents('.modal'),// 关闭modal时可以把qtip也remove掉
			},
			show: {
				ready: true
			},
			hide: {
				event: false,
			},
			style: {
				classes: 'qtip-bootstrap bg-danger text-danger'
			}
		})
	},
	hideTip : function(obj){
		$(obj).parent().removeClass('has-error');
		$(obj).parent().addClass('has-success');
		$(obj).qtip('destroy');
	},
	
	
};

