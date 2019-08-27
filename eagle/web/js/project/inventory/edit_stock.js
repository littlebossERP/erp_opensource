
if (typeof edit_stock === 'undefined')  edit_stock = new Object();

edit_stock={
	init:function(){
		//移除行
		$('.remove_row').click(function(){
			var node = $(this).parent().parent();
			node.remove();
		});
		
		//把某一列所有值恢复原来的值
		$('.restore_row').click(function(){
			var col_name = $(this).parent().attr('col_name');
			if(col_name != undefined && col_name != ''){
				$('input[name="'+col_name+'"]').each(function(){
					var old = $(this).attr('data-content');
					$(this).val(old);
				});
			}
		});
		
		//把某一列所有值清空
		$('.clear_row').click(function(){
			var col_name = $(this).parent().attr('col_name');
			if(col_name != undefined && col_name != ''){
				$('input[name="'+col_name+'"]').val('');
			}
		});
		
		//把某一列所有值清零
		$('.reset_row').click(function(){
			var col_name = $(this).parent().attr('col_name');
			if(col_name != undefined && col_name != ''){
				$('input[name="'+col_name+'"]').val('0');
			}
		});
		
		//批量修改某一列
		$('.edit_row').click(function(){
			var col_name = $(this).parent().attr('col_name');
			var en_name = $(this).parent().find('span').html();
			if(col_name != undefined && col_name != ''){
				$('.edit_info .edit_stock_edit_value').val('');
				bootbox.dialog({
					title: Translator.t("批量修改"+ en_name),
					className: "edit_info", 
					message: $('#dialog3').html(),
					buttons:{
						Ok: {  
							label: Translator.t("确认"),  
							className: "btn-success",  
							callback: function () {
								var val =  $('.edit_info  .edit_stock_edit_value').val();
								$('input[name="'+col_name+'"]').val(val);
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
	
	saveStock : function(){
		$.showLoading();
		$.ajax({
			type : 'post',
	        cache : 'false',
	        dataType: 'json',
	        data: $("#edit_stock_form").serialize(),
			url:'/inventory/inventory/bath-save-stock', 
			success: function (result) 
			{
				$.hideLoading();

				var Msg = "";
				if(typeof(result) == 'object'){
					if(typeof(result.error) !== 'undefined' && result.error !== ''){
						Msg += result.error;
					}
					else{
						for(var prop in result.msg){
							if(!isNaN(prop)){
								Msg += "<b style='color:red;line-height:25px;'>第"+ prop +"行：</b><br>";
								for(var prop2 in result.msg[prop]){
									if(!isNaN(prop2)){
										Msg += "&nbsp;&nbsp;&nbsp;&nbsp;"+ result.msg[prop][prop2] +"<br>";
									}
								}
							}
						}
					}

					if(Msg != ""){
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
							}
						});
					}
					else{
						window.location.reload();
					}
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
}