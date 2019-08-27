
function saveAllRulesByAjax(){
	if($('input[name=newname]').val() == ""){
		$('input[name=newname]').focus();
		$.alert('规则名称不能为空');
		return false;
	}
	
	if(($('select[name=shippingMethodId]').val() == "") || ($('select[name=shippingMethodId]').val() == null)){
		$.alert('运输服务不能为空');
		return false;
	}
	
	$('input[name=transportation_service_id]').val($('select[name=shippingMethodId]').val());
	
	$formdata = $('#rulesForm').serialize();
	var Url=global.baseUrl +'configuration/carrierconfig/saveshippingrules';
	$.ajax({
        type : 'post',
        cache : 'false',
        data : $formdata,
		url: Url,
        success:function(response) {
        	if(response[0] == 0){
        		$.alert(response.substring(2));
        		
        		if(undefined == $('#search_carrier_code').val()){
        			location.reload();
        		}else{
        			if(($('#search_tab_active').val() == 'customexcel') || ($('#search_tab_active').val() == 'customtracking')){
//        				window.location.search='tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();
        				
        				window.location.href=global.baseUrl+'configuration/carrierconfig/index?tab_active='+$('#search_tab_active').val()+'&tcarrier_code='+$('#search_carrier_code').val();
        			}else{
//        				window.location.search='tcarrier_code='+$('#search_carrier_code').val();
        				
        				window.location.href=global.baseUrl+'configuration/carrierconfig/index?tcarrier_code='+$('#search_carrier_code').val();
        			}
        		}
	        }
        	else{
        		$.alert(response.substring(2));
	        }
        }
    });
}

//将所有的右侧窗口关闭
function closeAllRightModals(){
	$('.rightModal').each(function(){
		$(this).hide();
	});
}
function setMyCity(){
	var val = $('textarea[name^=Mycity]').val();
	$('input[name=items_location_city]').val(val);
	$('.mycity_value').text(val);
}
function setRecProvince(){
	var val = $('textarea[name^=Recprovince]').val();
	$('input[name=receiving_provinces]').val(val);
	$('.recprovince_value').text(val);
}
function setRecCity(){
	var val = $('textarea[name^=Reccity]').val();
	$('input[name=receiving_city]').val(val);
	$('.reccity_value').text(val);
}
//将买家支付运费显示出来
function set_freight(){
	var min = $('input[name^=minfreight]').val();
	var max = $('input[name^=maxfreight]').val();
	$('input[name="freight_amount[min]"]').val(min);
	$('input[name="freight_amount[max]"]').val(max);
	$('.freight_value').text(min+" USD~"+max+" USD");
}
//将总金额显示出来
function set_totalamount(){
	var min = $('input[name^=minamount]').val();
	var max = $('input[name^=maxamount]').val();
	$('input[name="total_amount[min]"]').val(min);
	$('input[name="total_amount[max]"]').val(max);
	$('.totalamount_value').text(min+" USD~"+max+" USD");
}
//将总重量显示出来
function set_totalweight(){
	var min = $('input[name^=minweight]').val();
	var max = $('input[name^=maxweight]').val();
	$('input[name="total_weight[min]"]').val(min);
	$('input[name="total_weight[max]"]').val(max);
	$('.totalweight_value').text(min+" g~"+max+" g");
}
//将已选择的平台、账号、站点显示出来
function setSources(){
	var sourceHtml = "<div>平台：", siteHtml = "<div>站点：", seleruseridHtml = "<div>账号：";
	$('input:checkbox[name^="sites"]:checked').each(function(){
		obj = $(this);
		objPa = $(this).parent().parent().parent().hasClass('sr-only');
		platform = $(this).parent().parent().attr('platform');
		if(!objPa){
			siteHtml += '<label><input type="checkbox" name="sources[site]['+platform+'][]" value="'+obj.val()+'" checked>'+obj.parent().text()+' ('+platform+')</label>';
		}
	});
	$('input:checkbox[name^="selleruserids"]:checked').each(function(){
		obj = $(this);
		objPa = $(this).parent().parent().parent().hasClass('sr-only');
		platform = $(this).parent().parent().attr('platform');
		if(!objPa){
			seleruseridHtml += '<label><input type="checkbox" name="sources[selleruserid]['+platform+'][]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
		}
	});
	$('input:checkbox[name^="sourceCheck"]:checked').each(function(){
		obj = $(this);
		sourceHtml += '<label><input type="checkbox" name="sources[source][]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
	});
	siteHtml += "</div>";seleruseridHtml += "</div>";sourceHtml += "</div>";
	$('.sources_value').html(sourceHtml+siteHtml+seleruseridHtml);
}
//将已选择的商品标签显示出来
function setTag(){
	var htm = "";
	$('input:checkbox[name^="product_tags"]:checked').each(function(){
		obj = $(this);
		htm += '<label><input type="checkbox" name="product_tag[]" value="'+obj.val()+'" checked>'+obj.parent().text()+'</label>';
	});
	$('.product_tag_value').html(htm);
}
//将已设置的邮编显示出来
function setPostalcode(){
	$('#postal_code_err').css('display','none');
	var val = "";
	var text = "";
	var is_null = 0;
	var type = $('select[name=postal_code_type]').val();
	if(type == 'type_start'){
		val = $('.postal_code').find('input[name="start_str"]').val();
		text = '开头: '+ val;
		
		if(val.replace(/(^\s*)|(\s*$)/g, "") == '')
			is_null = 1;
	}
	else if(type == 'type_contains'){
		val = $('.postal_code').find('input[name="contains_str"]').val();
		text = '包含: '+ val;
		
		if(val.replace(/(^\s*)|(\s*$)/g, "") == '')
			is_null = 1;
	}
	else if(type == 'type_start_contains'){
		var str = $('.postal_code').find('input[name="start_str"]').val();
		if(str.replace(/(^\s*)|(\s*$)/g, "") == '')
			is_null = 1;
		val = str;
		text = '开头和包含内容: '+ str;
		str = $('.postal_code').find('input[name="contains_str"]').val();
		if(str.replace(/(^\s*)|(\s*$)/g, "") == '')
			is_null = 1;
		val += ","+ str
		text += ' , '+ str;
	}
	else if(type == 'type_range'){
		var str = $('.postal_code').find('input[name="range_count"]').val();
		if(str.replace(/(^\s*)|(\s*$)/g, "") == '')
			is_null = 1;
		val = str;
		text = '范围: 前 '+ str +'位, ';
		str = $('.postal_code').find('input[name="range_min"]').val();
		if(str.replace(/(^\s*)|(\s*$)/g, "") == '')
			is_null = 1;
		val += ","+ str;
		text += str +" - ";
		str = $('.postal_code').find('input[name="range_max"]').val();
		if(str.replace(/(^\s*)|(\s*$)/g, "") == '')
			is_null = 1;
		val += ","+ str;
		text += str;
	}
	else{
		return;
	}

	//判断是否输入内容不全
	if(is_null){
		$('#postal_code_err').css('display','block');
		$('#postal_code_err').html('请输入完整内容');
	}
	else{
		//判断是否已存在
		var is_exist = 0;
		$('.postal_code_value input').each(function(){
			if($(this).val() == type+","+val){
				is_exist = 1;
			}
		});
		
		if(is_exist == 1){
			$('#postal_code_err').css('display','block');
			$('#postal_code_err').html('该内容已存在');
		}
		else{
			var htm = '<label style="padding-right:10px;"><input type="checkbox" name="postal_code[]" value="'+type+","+val+'" checked> '+text+'</label>';
			$('.postal_code_value').append(htm);
		}
	}
}
function siteChange(){
	$('input[name^=site]').each(function(){
		var n = $(this).val();
		var platform = $(this).parent().parent().attr('platform');
		if($(this).prop('checked')){
			$("."+platform+n).removeClass('sr-only');
		}else{
			$("."+platform+n).addClass('sr-only');
		}
	})
}

//弹出新窗体国家选择
function selected_country_rule(distribution_type){
	closeAllRightModals();
	$('.full_rightDIV').css('width','0');
	$('.full_leftDIV').css('width','1220');
	$tmp=$('#over-lay').children().eq(1).hide();
	
	var selected_country = '';
	if(distribution_type == 'receiving_country'){
		$("[name='receiving_country[]']").each(function(){
			if($(this).prop("checked")==true){
				selected_country += $(this).val()+',';
			}
		});
	}else if(distribution_type == 'items_location_country'){
		$("[name='items_location_country[]']").each(function(){
			if($(this).prop("checked")==true){
				selected_country += $(this).val()+',';
			}
		});
	}
	
	
	if(selected_country == undefined){
		selected_country = '';
	}

	selected_country = selected_country.substring(0,selected_country.length-1);
	
	open_country_selected(selected_country, function(){
		$('.selected_country_save').click(function(){
			continentTypeArr = sure_country();

			tmp_selected_country_code = continentTypeArr[1];
			tmp_selected_country = continentTypeArr[0];

			tmp_selected_country_code_arr = tmp_selected_country_code.split(',');
			tmp_selected_country_arr = tmp_selected_country.split(',');

			var htm = "";

			if(tmp_selected_country_code != ''){
				$.each(tmp_selected_country_code_arr,function(key,value) {
					if(distribution_type == 'receiving_country'){
						htm += '<label><input type="checkbox" name="receiving_country[]" value="'+value+'" checked>'+tmp_selected_country_arr[key]+'</label>';
					}else if(distribution_type == 'items_location_country'){
						htm += '<label><input type="checkbox" name="items_location_country[]" value="'+value+'" checked>'+tmp_selected_country_arr[key]+'</label>';
					}
				});
			}
			
			if(distribution_type == 'receiving_country'){
				$('.receiving_value').html(htm);
			}else if(distribution_type == 'items_location_country'){
				$('.mycountry_value').html(htm);
			}
		});

		$('.modal-close').click(function(){
			$('#over-lay').children().eq(1).show();
		});
	});
}

//弹出新窗体买家选择运输服务
function buyer_transportation_service_rule(){
	closeAllRightModals();
	$('.full_rightDIV').css('width','0');
	$('.full_leftDIV').css('width','1220');
	
	$tmp=$('#over-lay').children().eq(1).hide();
	
	selected_service = '';
	
	open_buyer_transportation_service_selected(selected_service, function(){
		$('.selected_platform_service_save').click(function(){
			platformServiceTypeArr = sure_platform_service();

			tmp_platform_service_code_selected = platformServiceTypeArr[0];
			tmp_platform_service_name_selected = platformServiceTypeArr[1];
			tmp_platform_service_attr_name = platformServiceTypeArr[2];
			
			tmp_platform_service_code_arr = tmp_platform_service_code_selected.split(',');
			tmp_platform_service_name_arr = tmp_platform_service_name_selected.split(',');
			tmp_platform_service_attr_arr = tmp_platform_service_attr_name.split(',');
			
			if(tmp_platform_service_code_selected != ''){
				$.each(tmp_platform_service_code_arr,function(key,value) {
					var is_add_label = true;
					$(".buyer_transportation_service_value").find('label').find('input').each(function(){
						tmp_selected_service = $(this).val();
						tmp_selected_service_attr_name = $(this).attr('name');
						
						if((tmp_selected_service == value) && (tmp_selected_service_attr_name == 'buyer_transportation_service'+tmp_platform_service_attr_arr[key]+'[]')){
							is_add_label = false;
						}
					});
				
					if(is_add_label == true){
						$(".buyer_transportation_service_value").append('<label><input type="checkbox" name="buyer_transportation_service'+tmp_platform_service_attr_arr[key]+'[]" value="'+value+'" checked>'+tmp_platform_service_name_arr[key]+'</label>');
					}
				});
			}
		});

		$('.modal-close').click(function(){
			$('#over-lay').children().eq(1).show();
		});
	});
}

//打开买家选择运输服务界面
function open_buyer_transportation_service_selected(selected_service, callback){
	$.modal({
		  url:'/configuration/carrierconfig/buyer-transportation-service-list',
		  method:'get',
		  data:{selected_service:selected_service}
		},'选择买家选择运输服务',{footer:false,inside:false}).done(function($modal){
			callback();
			$('.iv-btn.btn-default.btn-sm.modal-close').click(function(){$modal.close();});
	});
}

//买家选择运输服务checked onclick事件
function buyer_transportation_service_click(obj){
	tmp_service_code = $(obj).val();
	tmp_service = $(obj).parent().text();
	tmp_service_attr_name = $(obj).attr('name');
	
	if($(obj).prop("checked")==true){
		set_buyer_transportation_service_div(tmp_service_code, tmp_service, tmp_service_attr_name);
	}else{
		remove_buyer_transportation_service_div(tmp_service_code, tmp_service_attr_name);
	}
}

//添加已选中的Html代码
function set_buyer_transportation_service_div(tmp_service_code, tmp_service, tmp_service_attr_name){
	var is_add_div = true;
	
	if($('#buyer_transportation_service_div').find('div').length == 0){
		is_add_div = true;
	}else{
		$('#buyer_transportation_service_div').find('div').each(function(){
			if(tmp_service_code == $(this).find('label').find('input').val()){
				is_add_div = false;
			}
		});
	}
	
	if(is_add_div == true){
		$('#buyer_transportation_service_div').append(
				'<div class="platform_label_div" >'+
				'<label><input type="checkbox" value="'+tmp_service_code+'" name="'+tmp_service_attr_name+'" onclick=buyer_transportation_service_remove(this) checked>'+tmp_service_attr_name+tmp_service+'</label>'+
				'</div>');
	}
}

//取消已选
function remove_buyer_transportation_service_div(tmp_service_code, tmp_service_attr_name){
	$('#buyer_transportation_service_div').find('div').each(function(){
		if((tmp_service_code == $(this).find('label').find('input').val()) && (tmp_service_attr_name == $(this).find('label').find('input').attr('name'))){
			$(this).remove();
		}
	});
}

function buyer_transportation_service_remove(obj){
	tmp_service_code = $(obj).val();
	tmp_service_attr_name = $(obj).attr('name');
	
	$('.platform_selected').find('div').each(function(){
		if((tmp_service_code == $(this).find('label').find('input').val()) && (tmp_service_attr_name == $(this).find('label').find('input').attr('name'))){
			$(this).find('label').find('input').prop("checked",false);
		}
	});
	
	$(obj).parent().parent().remove();
}

//全选事件
function all_platform_service_ck_click(obj){
	var div_id = $(obj).val();
	
	if($(obj).prop("checked")==true){
		$('#'+div_id).find('.platform_label_div:visible').each(function(){
			$(this).find('label').find('input').prop("checked",true);
			
			tmp_service_code =  $(this).find('label').find('input').val();
			tmp_service = $(this).text();
			tmp_service_attr_name = $(this).find('label').find('input').attr('name');
			
			set_buyer_transportation_service_div(tmp_service_code, tmp_service, tmp_service_attr_name);
		});
	}else{
		$('#'+div_id).find('.platform_label_div:visible').each(function(){
			$(this).find('label').find('input').prop("checked",false);
			
			tmp_country_code = $(this).find('label').find('input').val();
			tmp_service_attr_name = $(this).find('label').find('input').attr('name');
			
			remove_buyer_transportation_service_div(tmp_country_code, tmp_service_attr_name);
		});
	}
}

//清空已选的服务
function selected_platform_service_clear(){
	$('#buyer_transportation_service_div').html('');
	$('.platform_label_div').find('label').find('input').prop("checked",false);
	$('.all_select_platform_service_div').find('label').find('input').prop("checked",false);
}

//确定查找的服务
function sure_platform_service(){
	var platform_service_code_selected = '';
	var platform_service_name_selected = '';
	var platform_service_attr_name = '';
	
	$('#buyer_transportation_service_div').find('.platform_label_div').each(function(){
		platform_service_code_selected += $(this).find('label').find('input').val() + ',';
		platform_service_attr_name += $(this).find('label').find('input').attr('name') + ',';
		
		tmp_platform_service_name_selected = $(this).text();
		tmp_platform_service_name_selected = $.trim(tmp_platform_service_name_selected);
		
		platform_service_name_selected += tmp_platform_service_name_selected + ',';
	});
	
	platform_service_code_selected = platform_service_code_selected.substring(0,platform_service_code_selected.length-1);
	platform_service_name_selected = platform_service_name_selected.substring(0,platform_service_name_selected.length-1);
	platform_service_attr_name = platform_service_attr_name.substring(0,platform_service_attr_name.length-1);
	
	var platformServiceTypeArr = new Array(platform_service_code_selected, platform_service_name_selected, platform_service_attr_name);
	
	return platformServiceTypeArr;
}

//ebay站点切换显示买家运输服务
function eaby_site_change(obj){
//	alert($(obj).val());
	$('#ebay_platform_selected').find('div').hide()
	
	var site_type = $(obj).val();
	if(site_type == '') return false;
	
	$('.platform_label_div.'+site_type).show();
}

//查找框
function txt_search_selected_buyer_service(obj){
	search_code = $(obj).val();
//	search_code = search_code.toUpperCase();
	
	var continentTypeArr = new Array('aliexpress', 'ebay', 'amazon', 'cdiscount', 'priceminister', 'newegg');
	
	$.each(continentTypeArr,function(name,value) {
		$('#'+value+'_platform_selected').find('.platform_label_div').hide();
		
		$('#'+value+'_platform_selected').find('.platform_label_div:contains('+search_code+')').each(function(){
			$(this).show();
		});
		
		countryLen = 0;
		$('#'+value+'_platform_selected').find('.platform_label_div').each(function(){
			if($(this).css("display") == 'block'){
				countryLen++;
			}
		});
		
		if((search_code == '') || (countryLen == 0)){
			$('#'+value+'_platform_li').find('span').text('');
		}else{
			$('#'+value+'_platform_li').find('span').text(countryLen);
		}
	});
}

//添加SKU
function txt_sku_add_click(){
//	alert($('input[name=txt_sku_add]').val());
	
	var skulist = $('input[name=txt_sku_add]').val();
	
	if(skulist != null && skulist != ''){
		$.ajax({
			type : 'post',
	        cache : 'false',
	        dataType: 'json',
	        data: {skulist : skulist},
			url:'/configuration/carrierconfig/check-sku-info', 
			success: function (result){
				if(result['not_exist_sku'].length == 0){
					if(result['sku_aliases'].length > 0){
						//商品别名
						var sku_aliases = '';
						if(result['sku_aliases'].length){
							for(var n = 0; n < result['sku_aliases'].length; n++){
								var data = result['sku_aliases'][n];
								sku_aliases = sku_aliases + data + "\n";
							}
						}
						
						if(sku_aliases != ''){
							$.alertBox('<p class="text-success">SKU为商品别名，会自动转换为对应主SKU：'+result.sku_root+'</p>');
						}
						
						result.skulist = result.sku_root;
					}
					
					is_add_label = true;
					$(".sku_value").find('label').find('input').each(function(){
						tmp_sku_value = $(this).val();
						
						if(tmp_sku_value == result.skulist){
							is_add_label = false;
						}
					});
					
					if(is_add_label == true){
						$('.sku_value').append('<label><input type="checkbox" name="sku_group[]" value="'+result.skulist+'" checked>'+result.skulist+'</label>');
					}
				}else{
					//不存在的sku
					var not_exist_sku = '';
					if(result['not_exist_sku'].length){
						for(var n = 0; n < result['not_exist_sku'].length; n++){
							var data = result['not_exist_sku'][n];
							not_exist_sku = not_exist_sku + data + "\n";
						}
					}
					
					$.alertBox('<p class="text-warn">添加失败，商品模块不存在该SKU：'+not_exist_sku+'</p>');
				}
				
				$('input[name=txt_sku_add]').val('');
			},
		});
	}
}

//添加物品所在地区(ebay)
function txt_myprovince_add_click(){
	var myprovincelist = $('input[name=txt_myprovince_add]').val();
	
	if(myprovincelist != null && myprovincelist != ''){
		
		is_add_label = true;
		$(".myprovince_value").find('label').find('input').each(function(){
			if($(this).val() == myprovincelist){
				is_add_label = false;
			}
		});
		
		if(is_add_label == true){
			$('.myprovince_value').append('<label><input type="checkbox" name="myprovince_group[]" value="'+myprovincelist+'" checked>'+myprovincelist+'</label>');
		}
		
		$('input[name=txt_myprovince_add]').val('');
	}
}

//指定添加SKU
function selectProdRules(){
	$(this).selectProduct('open',{
		afterSelectProduct:function(prodList){
			$.each(prodList,function(key,value) {
				is_add_label = true;
				$(".sku_value").find('label').find('input').each(function(){
					tmp_sku_value = $(this).val();
					
					if(tmp_sku_value == prodList[key].sku){
						is_add_label = false;
					}
				});
				
				if(is_add_label == true){
					$('.sku_value').append('<label><input type="checkbox" name="sku_group[]" value="'+prodList[key].sku+'" checked>'+prodList[key].sku+'</label>');
				}
			});
		},
	});
}

//添加国家币种
function addCurrency(currency_type, obj){
	if($('#table_currency').find('tbody').find('tr').length == 0){
		$('#table_currency').show();
	}
	
	$('#table_currency').find('tbody').append(
			'<tr>'+
			'<td>'+$(obj).text()+'</td>'+
			'<td>'+currency_type+'</td>'+
			'<td class="form-inline">'+
				'<div class="input-group input-group-sm currency_div_w currency_div_margin_right">'+
					'<input data="min" type="number" name="total_amount_new['+currency_type+'][min]" class="form-control" onkeyup="currencyNumChange(this)" onchange="currencyNumChange(this)">'+
					'<span class="input-group-addon">-</span>'+
					'<input data="max" type="number" name="total_amount_new['+currency_type+'][max]" class="form-control" onkeyup="currencyNumChange(this)" onchange="currencyNumChange(this)">'+
				'</div>'+
				'<span></span>'+
			'</td>'+
			'<td><button type="button" onclick="removeCurrency(this)" class="btn btn-sm btn-default">删除</button></td>'+
		'</tr>'
	);
	
	$(obj).hide();
	
}

//更改值数值时，更改提示
function currencyNumChange(obj){
//	console.log();
	
	type = $(obj).attr('data');
	min_val = '';
	max_val = '';
	
	if(type == 'min'){
		min_val = $(obj).val();
		max_val = $(obj).next().next().val();
	}else{
		min_val = $(obj).prev().prev().val();
		max_val = $(obj).val();
	}
	
	if(min_val == '0'){
		min_val = '';
	}
	
	if(max_val == '0'){
		max_val = '';
	}
	
	if((min_val != '') && ((max_val == '') || (max_val == '0'))){
		$(obj).parent().next().html('>='+min_val);
	}else if(((min_val == '') || (min_val == '0')) && (max_val != '')){
		$(obj).parent().next().html('<='+max_val);
	}else if(((min_val == '') && (max_val == '')) || ((min_val == '0') && (max_val == '0'))){
		$(obj).parent().next().html('');
	}else {
		$(obj).parent().next().html('范围:'+min_val+' - '+max_val);
	}
}

//删除对应币种设置
function removeCurrency(obj){
	var currency = $(obj).parent().parent().children(':first').next().text();
	$('.btn_currency_'+currency).show();
	
	$(obj).parent().parent().remove();
	
	if($('#table_currency').find('tbody').find('tr').length == 0){
		$('#table_currency').hide();
	}
}

function set_totalamount_new(){
	if($('#table_currency').find('tbody').find('tr').length > 0){
		$('#table_currency').find('tbody').find('tr').each(function(){
			var min_val = '';
			var max_val = '';

			$(this).children().next().next().children().children().each(function(){
				if($(this).attr('data') == 'min'){
					min_val = $(this).val();
				}else if($(this).attr('data') == 'max'){
					max_val = $(this).val();
				}
			});

			if((min_val != '') && ((max_val == '') || (max_val == '0'))){
				$(this).children().next().next().children().next().html('>='+min_val);
			}else if(((min_val == '') || (min_val == '0')) && (max_val != '')){
				$(this).children().next().next().children().next().html('<='+max_val);
			}else if(((min_val == '') && (max_val == '')) || ((min_val == '0') && (max_val == '0'))){
				$(this).children().next().next().children().next().html('');
			}else {
				$(this).children().next().next().children().next().html('范围:'+min_val+' - '+max_val);
			}
		});
	}
}
