
if (typeof matching === 'undefined')  matching = new Object();

matching.create_pro={
	init:function(){
		$("#create_product_table textarea[name='root_sku[]']").unbind('change').bind('change',function(){
			//查询是否存在重复的SKU
		});
		
		//移除行
		$('.remove_row').click(function(){
			var node = $(this).parent().parent();
			var type = node.attr("node_type");
			//子节点
			if(type == 'chli'){
				//查询上级父节点
				var fat_id = node.attr("fat_id");
				if(fat_id != ''){
					var father = $('tr[name="matcing_create_'+ fat_id +'"]').first();
					var span = father.find('input[name="rowspan[]"]').val();
					father.find("td").each(function(){
						if(!isNaN($(this).attr("rowspan"))){
							span = $(this).attr("rowspan") - 1;
							$(this).attr("rowspan", span);
						}
					});
					father.find('input[name="rowspan[]"]').val(span);
					node.remove();
				}
			}
			//父亲节点
			else if(type == 'fat'){
				var name = node.attr('name');
				var fat_id = name.replace('matcing_create_', '');
				if(fat_id != ''){
					var chli_list = $('tr[fat_id="'+ fat_id +'"]');
					if(chli_list.length > 0){
						//第一个子节点信息赋值到父节点
						var $chli = chli_list.first();
						var html = $chli.find('td[name="td_chli_sku"]').first().html();
						node.find('td[name="td_chli_sku"]').html(html);
						html = $chli.find('td[name="td_chli_info"]').first().html();
						node.find('td[name="td_chli_info"]').html(html);
						//清除第一个子节点
						$chli.remove();
						//父节点并行数减1
						var span = node.find('input[name="rowspan[]"]').val();
						node.find("td").each(function(){
							if(!isNaN($(this).attr("rowspan"))){
								span = $(this).attr("rowspan") - 1;
								$(this).attr("rowspan", span);
							}
						});
						node.find('input[name="rowspan[]"]').val(span);
					}
					else{
						node.remove();
					}
				}
			}
			//普通节点
			else{
				node.remove();
			}
			
		});
		
		//把某一列所有值恢复原来的值
		$('.restore_row').click(function(){
			var col_name = $(this).parent().attr('col_name');
			if(col_name != undefined && col_name != ''){
				$('textarea[name="'+col_name+'"]').each(function(){
					var old = $(this).attr('data-content');
					$(this).val(old);
				});
			}
		});
		
		//把某一列所有值清空
		$('.clear_row').click(function(){
			var col_name = $(this).parent().attr('col_name');
			if(col_name != undefined && col_name != ''){
				$('textarea[name="'+col_name+'"]').val('');
			}
		});
		
		//批量修改某一列
		$('.edit_row').click(function(){
			var col_name = $(this).parent().attr('col_name');
			var en_name = $(this).parent().find('span').html();
			if(col_name != undefined && col_name != ''){
				$('.edit_info .create_product_edit_value').val('');
				bootbox.dialog({
					title: Translator.t("批量修改"+ en_name),
					className: "edit_info", 
					message: $('#dialog3').html(),
					buttons:{
						Ok: {  
							label: Translator.t("确认"),  
							className: "btn-success",  
							callback: function () {
								var val =  $('.edit_info  .create_product_edit_value').val();
								$('textarea[name="'+col_name+'"]').val(val);
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
	
	createProBtn:function(){
		//查询是否存在重复的SKU
		var exist_sku = new Array();
		var msg = '';
		var is_empty = false;
		//SKU
		$("#create_product_table textarea[name='root_sku[]'], #create_product_table textarea[name='chli_sku[]']").each(function(){
			if($(this).val() == ''){
				 $(this).css('border','2px solid red');
				 is_empty = true;
			}
			else if($.inArray($(this).val(), exist_sku) >= 0){
			    $(this).css('border','2px solid red');
			    msg += $(this).val() +"<br>";
			}
			else{
				exist_sku.push($(this).val());
				$(this).css('border-color','');
			}
		})
		
		//不存在可新增的SKU
		if(exist_sku.length == 0){
			bootbox.alert("<span style='color: red;'>不存在可新增的商品！</span>");
			return false;
		}
		//当存在SKU为空时，不可保存
		if(is_empty){
			bootbox.alert("<span style='color: red;'>存在SKU为空的行！</span>");
			return false;
		}
		//当存在重复SKU时，禁止生成商品
		else if(msg != ''){
			msg = "<span style='color: red;'>生成的商品中，以下SKU存在重复：</span><br><br>"+ msg;
			bootbox.alert(msg);
			return false;
		}
		else{
			$.showLoading();
			$.ajax({
				type : 'post',
		        cache : 'false',
		        dataType: 'json',
		        data: $("#creat_pro_form").serialize(),
				url:'/catalog/matching/create-product', 
				success: function (result) 
				{
					$.hideLoading();
	
					var Msg = "";
					if(typeof(result) == 'object'){
						if(typeof(result.error) !== 'undefined' && result.error !== ''){
							Msg += result.error;
						}
						else{
							Msg += "<p style='font-size:18px;line-height:25px;'>成功新增商品：<span style='color:#91c854;'>"+ result.successQty +"</span> 个，";
							Msg += "失败：<span style='color:#ed5466'>"+ result.failQty +"</span> 个</p>";
							
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
								//matching.list.searchButtonClick('', true);
								if(result.failQty == 0){
									window.location.reload();
								}
								else{
									//去掉已生成的商品
									for(var prop in result.itemid){
										$('tr[name="matching_item_'+result.itemid[prop]+'"]').remove();
										$('tr[name="matcing_create_'+result.itemid[prop]+'"]').remove();
									}
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
		}
	},
}