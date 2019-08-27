//input 
function selected_country_click(){
	var selected_country = $("[name='selected_country_code']").val();
	
	if(selected_country == undefined){
		selected_country = '';
	}
	
	open_country_selected(selected_country, function(){
		$('.selected_country_save').click(function(){
			continentTypeArr = sure_country();
			
			$("[name='selected_country']").val(continentTypeArr[0]);
			$("[name='selected_country_code']").val(continentTypeArr[1]);
		});
	});
	
}

//单选框时
function selected_country_click2(){
	var selected_country = $("[name='selected_country_code']").val();
	
	if(selected_country == undefined){
		selected_country = '';
	}
	
	open_country_selected2(selected_country, function(){
		$('.selected_country_save').click(function(){
			continentTypeArr = sure_country();
			
			$("[name='selected_country']").val(continentTypeArr[0]);
			$("[name='selected_country_code']").val(continentTypeArr[1]);
		});
	});
	
}
//单选框时
function open_country_selected2(selected_country, callback){
	$.modal({
		  url:'/util/default/get-country-list',
		  method:'get',
		  data:{selected_country:selected_country,is_radio:1}
		},'选择国家',{footer:false,inside:false}).done(function($modal){			
			callback();		
			$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});
	});
}

function open_country_selected(selected_country, callback){
	$.modal({
		  url:'/util/default/get-country-list',
		  method:'get',
		  data:{selected_country:selected_country}
		},'选择国家',{footer:false,inside:false}).done(function($modal){
//			$('.selected_country_save').click(function(){sure_country();});
//			$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});
			
			callback();
			
			$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});
	});
}

//动态显示添加常用国家的+号
function show_add_span(obj){
	$(obj).find('a').find('span').show();
}

//动态隐藏添加常用国家的+号
function hidden_add_span(obj){
	$(obj).find('a').find('span').hide();
}

//添加到已选择的国家
function country_click(obj){
	tmp_country_code = $(obj).val();
	tmp_country = $(obj).parent().text();
	tmp_country_en = $(obj).parent().find('input').attr('en_name');
	
	if(tmp_country_en.indexOf("-radio")>-1){
		$(obj).prop("checked",true);
	}
	
	if($(obj).prop("checked")==true){
		setSelected_country_div(tmp_country_code, tmp_country, tmp_country_en);
		
		$("#country_common_"+tmp_country_code+"_div").find('label').find('input').prop("checked",true);
	}else{
		$('#country_selected_'+tmp_country_code+'_div').remove();
		
		$("#country_common_"+tmp_country_code+"_div").find('label').find('input').prop("checked",false);
	}
	
}

//隐藏已选择的国家
function country_remove_click(obj){
	tmp_country_code = $(obj).val();
	
	$("#country_label_div_"+tmp_country_code).find('label').find('input').prop("checked",false);
	$("#country_common_"+tmp_country_code+"_div").find('label').find('input').prop("checked",false);
	
	$(obj).parent().parent().remove();
}

//确定查找的国家
function sure_country(){
	var country_code_selected = '';
	var country_name_selected = '';
	
	$('#selected_country_div').find('.country_label_div').each(function(){
		country_code_selected += $(this).find('label').find('input').val() + ',';
		
		$tmp_country_name_selected = $(this).text();
		$tmp_country_name_selected = $.trim($tmp_country_name_selected);
		
		country_name_selected += $tmp_country_name_selected + ',';
	});
	
	country_code_selected = country_code_selected.substring(0,country_code_selected.length-1);
	country_name_selected = country_name_selected.substring(0,country_name_selected.length-1);
	
	
//	$("[name='selected_country']").val(country_name_selected);
//	$("[name='selected_country_code']").val(country_code_selected);
	
	var continentTypeArr = new Array(country_name_selected, country_code_selected);
	
	return continentTypeArr;
}

//添加到常用国家
function add_common_country(obj){
	tmp_country_code = $(obj).parent().find('label').find('input').val();
	tmp_country = $(obj).parent().find('label').text();
	
	tmp_country_en = $(obj).parent().find('label').find('input').attr('en_name');
	
	if($("#country_common_"+tmp_country_code+"_div").length>0){
		$.alertBox('<p class="text-warn">不能重复添加常用国家</p>');
		return false;
	}else{
		$.ajax({
			url: "/util/default/set-common-country",
			type: 'post',
			data: {type:'add', country_code : tmp_country_code},
			dataType:"json",
			success: function(result) {
				if (result.error == 0){
					$.alertBox('<p class="text-success">'+result.msg+'</p>');
					return false;
				}else{
					$.alertBox('<p class="text-warn">'+result.msg+'</p>');
					return false;
				}
			},
			error: function() {
				bootbox.alert('内部错误！请联系客服');
			}
		});
		
		tmp_checked = '';
		if($('#country_selected_'+tmp_country_code+'_div').length>0){
			tmp_checked = 'checked';
		}
		
		$('#common_country_selected').append(
				'<div class="country_label_div" id="country_common_'+tmp_country_code+'_div" onmouseover="show_add_span(this)" onmouseout="hidden_add_span(this)">'+
				'<label><input type="checkbox" value="'+tmp_country_code+'" en_name="'+tmp_country_en+'" onclick="common_country_click(this)" '+tmp_checked+'>'+tmp_country+'</label>'+
				'<a href="javascript:void(0);"><span style="display: none;" class="add_span glyphicon glyphicon-remove" onclick="common_country_remove(this)"></span></a>'+
				'</div>');
	}
}

//常用国家checked onclick事件
function common_country_click(obj){
	tmp_country_code = $(obj).val();
	tmp_country = $(obj).parent().text();
	tmp_country_en = $(obj).parent().find('input').attr('en_name');
	
	if(tmp_country_en.indexOf("-radio")>-1){
		$(obj).prop("checked",true);
	}
	
	if($(obj).prop("checked")==true){
		$("#country_label_div_"+tmp_country_code).find('label').find('input').prop("checked",true);
		
		setSelected_country_div(tmp_country_code, tmp_country, tmp_country_en);
	}else{
		$("#country_label_div_"+tmp_country_code).find('label').find('input').prop("checked",false);
		$("#country_selected_"+tmp_country_code+"_div").remove();
	}
}

//添加已选中的Html代码
function setSelected_country_div(tmp_country_code, tmp_country, tmp_country_en){
	if(tmp_country_en.indexOf("-radio")>-1){
		if($("#country_selected_"+tmp_country_code+"_div").length <= 0){
			$('#selected_country_div').html(
					'<div class="country_label_div" id="country_selected_'+tmp_country_code+'_div" onmouseover="show_add_span(this)" onmouseout="hidden_add_span(this)">'+
					'<label><input type="checkbox" en_name="'+tmp_country_en+'" value="'+tmp_country_code+'" onclick="country_remove_click(this)" checked>'+tmp_country+'</label>'+
					'<a href="javascript:void(0);" onclick=add_common_country(this)><span style="display: none;" class="add_span glyphicon glyphicon-plus" onclick=""></span></a>'+
					'</div>');
		}
	}
	else{
		if($("#country_selected_"+tmp_country_code+"_div").length <= 0){
			$('#selected_country_div').append(
					'<div class="country_label_div" id="country_selected_'+tmp_country_code+'_div" onmouseover="show_add_span(this)" onmouseout="hidden_add_span(this)">'+
					'<label><input type="checkbox" en_name="'+tmp_country_en+'" value="'+tmp_country_code+'" onclick="country_remove_click(this)" checked>'+tmp_country+'</label>'+
					'<a href="javascript:void(0);" onclick=add_common_country(this)><span style="display: none;" class="add_span glyphicon glyphicon-plus" onclick=""></span></a>'+
					'</div>');
		}
	}
}

//常用国家删除
function common_country_remove(obj){
	tmp_country_code = $(obj).parent().parent().find('label').find('input').val();
	
	$.ajax({
		url: "/util/default/set-common-country",
		type: 'post',
		data: {type : 'del',country_code : tmp_country_code},
		dataType:"json",
		success: function(result) {
			if (result.error == 0){
				$.alertBox('<p class="text-success">'+result.msg+'</p>');
				return false;
			}else{
				$.alertBox('<p class="text-warn">'+result.msg+'</p>');
				return false;
			}
		},
		error: function() {
			bootbox.alert('内部错误！请联系客服');
		}
	});
	
	$(obj).parent().parent().remove();
}

//全选事件
function all_country_ck_click(obj){
	var div_id = $(obj).val();
	
	if($(obj).prop("checked")==true){
		$('#'+div_id).find('.country_label_div:visible').each(function(){
			$(this).find('label').find('input').prop("checked",true);
			
			var tmp_country_code = $(this).find('label').find('input').val();
			var tmp_country_en = $(this).find('label').find('input').attr('en_name')
			
			$('#country_label_div_'+tmp_country_code).find('label').find('input').prop("checked",true);
			$('#country_common_'+tmp_country_code+'_div').find('label').find('input').prop("checked",true);
			
			setSelected_country_div(tmp_country_code, $(this).find('label').text(), tmp_country_en);
		});
	}else{
		$('#'+div_id).find('.country_label_div:visible').each(function(){
			$(this).find('label').find('input').prop("checked",false);
			
			var tmp_country_code = $(this).find('label').find('input').val();
			
			$('#country_label_div_'+tmp_country_code).find('label').find('input').prop("checked",false);
			$('#country_common_'+tmp_country_code+'_div').find('label').find('input').prop("checked",false);
			
			$("#country_selected_"+$(this).find('label').find('input').val()+"_div").remove();
		});
	}
}

//查找框
function txt_search_selected_country(obj){
	search_code = $(obj).val();
	search_code = search_code.toUpperCase();
	
	var continentTypeArr = new Array('common', 'asia', 'europe', 'north_america', 'south_america', 'oceania', 'africa', 'other');
	
	$.each(continentTypeArr,function(name,value) {
		$('#'+value+'_country_selected').find('.country_label_div').hide();
		
		$('#'+value+'_country_selected').find('.country_label_div:contains('+search_code+')').each(function(){
			$('#'+$(this).attr('id')).show();
		});
		
		$('#'+value+'_country_selected').find('.country_label_div').each(function(){
			tmp_country_code = $(this).find('label').find('input').val();
			
			if(search_code.length != 2){
				tmp_country_en_name = $(this).find('label').find('input').attr('en_name');
			}else{
				tmp_country_en_name = '';
			}

	        var tmpIndexOf = tmp_country_code.indexOf(search_code);
	        
	        if (tmp_country_en_name != undefined){
	        	var tmpIndexOfEn = tmp_country_en_name.indexOf(search_code);
	        }else{
	        	tmpIndexOf = -1;
	        }
	        
	        if((tmpIndexOf >= 0) || tmpIndexOfEn >= 0){
	        	$('#'+$(this).attr('id')).show();
	        }
		});
		
		countryLen = 0;
		$('#'+value+'_country_selected').find('.country_label_div').each(function(){
			if($(this).css("display") == 'block'){
				countryLen++;
			}
		});
		
		if((search_code == '') || (countryLen == 0)){
			$('#'+value+'_country_li').find('span').text('');
		}else{
			$('#'+value+'_country_li').find('span').text(countryLen);
		}
	});
}

//清空已选的国家
function selected_country_clear(){
	$('#selected_country_div').html('');
	$('.country_label_div').find('label').find('input').prop("checked",false);
	$('.all_select_country_div').find('label').find('input').prop("checked",false);
}
