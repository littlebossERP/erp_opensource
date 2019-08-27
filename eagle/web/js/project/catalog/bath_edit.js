
if (typeof productList === 'undefined')  productList = new Object();

productList.bath_edit={
	init:function(){
		//移除行
		$('.remove_row').click(function(){
			$(this).parent().parent().remove();
		});
		
		//把某一列所有值恢复原来的值
		$('.restore_row').click(function(){
			var col_name = $(this).parent().attr('col_name');
			if(col_name != undefined && col_name != ''){
				if(col_name == '[commission_per]'){
					productList.bath_edit.change_plat()
				}
				else if(col_name == '[battery]'){
					$('select[name$="'+col_name+'"]').each(function(){
						var old = $(this).attr('data-content');
						$(this).val(old);
					});
				}
				else{
					$('textarea[name$="'+col_name+'"]').each(function(){
						var old = $(this).attr('data-content');
						$(this).val(old);
					});
				}
			}
		});
		
		//把某一列所有值清空
		$('.clear_row').click(function(){
			var col_name = $(this).parent().attr('col_name');
			if(col_name != undefined && col_name != ''){
				$('textarea[name$="'+col_name+'"]').val('');
			}
		});
		
		//批量修改某一列
		$('.edit_row').click(function(){
			var class_name = $(this).attr('edit_dialog');
			var col_name = $(this).parent().attr('col_name');
			var cn_name = $(this).parent().find('span').html();
			if(col_name != undefined && col_name != ''){
				$('.'+class_name+' input').val('');
				bootbox.dialog({
					title: Translator.t("批量修改"+ cn_name),
					className: class_name, 
					message: $('#dialog_'+class_name).html(),
					buttons:{
						Ok: {  
							label: Translator.t("确认"),  
							className: "btn-success",  
							callback: function () {
								if(class_name == 'edit_info_spec1'){
									//开头
									var startStr =  $('.'+class_name+' [name="startStr"]').val();
									if(startStr != undefined && startStr != ''){
										$('textarea[name$="'+col_name+'"]').each(function(){
											$(this).val(startStr + $(this).val());
										});
									}
									//结尾
									var endStr =  $('.'+class_name+' [name="endStr"]').val();
									if(endStr != undefined && endStr != ''){
										$('textarea[name$="'+col_name+'"]').each(function(){
											$(this).val($(this).val() + endStr);
										});
									}
									//替换
									var searchStr =  $('.'+class_name+' [name="searchStr"]').val();
									var replaceStr =  $('.'+class_name+' [name="replaceStr"]').val();
									if(searchStr != undefined && searchStr != '' && replaceStr != undefined){
										$('textarea[name$="'+col_name+'"]').each(function(){
											$(this).val($(this).val().replace(searchStr, replaceStr));
										});
									}
								}
								else if(class_name == 'edit_info_battery'){
									var val =  $('.'+class_name+' [name="battery"]').find("option:selected").val();
									if(val != undefined && val != ''){
										$('select[name$="'+col_name+'"]').val(val);
									}
								}
								else{
									var val =  $('.'+class_name+' .bath_edit_input').val();
									if(val != undefined && val != ''){
										$('textarea[name$="'+col_name+'"]').val(val);
									}
								}
							}
						}, 
						Cancel: {  
							label: Translator.t("取消"),  
							className: "btn-default",  
							callback: function () {  
							}
						}, 
					}
				});	
			}
		});
	},
	
	saveProduct:function(){
		$.showLoading();
		$.ajax({
			type : 'post',
	        cache : 'false',
	        dataType: 'json',
	        data: $("#bath_edit_pro_form").serialize(),
			url:'/catalog/product/save-bath-edit', 
			success: function (result) 
			{
				$.hideLoading();
				
				var Msg = "";
				if(typeof(result) == 'object'){
					if(typeof(result.error) !== 'undefined' && result.error !== ''){
						Msg += result.error;
					}
					else{
						Msg += "<p style='font-size:18px;line-height:25px;'>成功更新商品：<span style='color:#91c854;'>"+ result.successQty +"</span> 个，";
						Msg += "失败：<span style='color:#ed5466'>"+ result.failQty +"</span> 个</p>";
						
						for(var prop in result.msg){
							if(!isNaN(prop)){
								Msg += "<b style='color:red;line-height:25px;'>SKU: "+ result.msg[prop]['sku'] +"</b><br>";
								for(var prop2 in result.msg[prop]['list']){
									if(!isNaN(prop2)){
										Msg += "&nbsp;&nbsp;&nbsp;&nbsp;"+ result.msg[prop]['list'][prop2] +"<br>";
									}
								}
							}
						}
					}

					Msg = "<div style='height: 300px;overflow: auto;'>"+Msg+"</div>";
					bootbox.alert({
						buttons: {
							ok: {
								label: '确认',
								className: 'btn-primary'
							}
						},
						message: Msg,
						callback: function() {
							if(result.failQty == 0){
								window.location.reload();
							}
						}
					});
				}
				return true;
			},
			error: function()
			{
				bootbox.alert("出现错误，请联系客服求助...");
				$.hideLoading();
				return false;
			}
		});
	},
	
	//切换平台
	change_plat:function(){
		var val = $('#bath_edit_commission_platform').val();
		$('textarea[name$="[commission_per]"]').each(function(){
			$plat_val = $(this).attr(val);
			if($plat_val == undefined){
				$plat_val = ''
			}
			$(this).val($plat_val);
		});
	},
}